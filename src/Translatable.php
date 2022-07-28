<?php

namespace Huztw\Translator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * @method static trans($locale = null, $fallback = true) 預載入翻譯至預載入關聯和當前模型
 * @method \Illuminate\Database\Eloquent\Collection|static[] translatedAll($locale = null, $fallback = true, $columns = ['*']) 執行"select"語句並且翻譯結果
 */
trait Translatable
{
    /**
     * 翻譯語言
     *
     * @var string
     */
    protected static $transLocale;

    /**
     * 備用翻譯語言
     *
     * @var string
     */
    protected static $fallbackLocale;

    /**
     * Get all of the current model's translations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translations()
    {
        return $this->morphMany(Translator::translationModel(), 'translatable');
    }

    /**
     * Get current locale.
     *
     * @return string|null
     */
    public function getLocale()
    {
        return property_exists($this, 'locale') ? $this->locale : null;
    }

    /**
     * Set current locale.
     *
     * @param string $locale
     * @return void
     */
    public function setLocale($locale)
    {
        if (!property_exists($this, 'locale')) {
            return;
        }

        $this->locale = $locale;
    }

    /**
     * 設定要翻譯語系
     *
     * @param null|string $locale
     * @param bool|string $fallbackLocale
     *
     * @return void
     */
    public static function setTransLocale($locale = null, $fallbackLocale = true)
    {
        self::$transLocale    = is_null($locale) ? app()->getLocale() : $locale;
        self::$fallbackLocale = $fallbackLocale === true ? config('app.fallback_locale') : $fallbackLocale;
    }

    /**
     * 翻譯模型
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param null|string $locale
     * @param bool|string $fallbackLocale
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrans($query, $locale = null, $fallbackLocale = true)
    {
        static::setTransLocale($locale, $fallbackLocale);

        $callback = function ($query) {
            $query->where(function ($q) {
                $q->where('locale', self::$transLocale);

                if (self::$fallbackLocale && self::$transLocale != self::$fallbackLocale) {
                    $q->orWhere('locale', self::$fallbackLocale);
                }
            })->select([
                'id',
                'locale',
                'translatable_id',
                'translatable_type',
                'translated_key',
                'translated_value',
            ]);
        };

        if ($this->canTrans($this->getModelByTrans($query))) {
            $query->with(['translations' => $callback]);
        }

        foreach ($eagerLoads = $query->getEagerLoads() as $name => $constraints) {
            if (
                'translations' != Str::afterLast($name, '.')
                && !in_array("$name.translations", $eagerLoads)
                && $this->canTrans($this->getModelByTrans($query, $name))
            ) {
                $query->with(["$name.translations" => $callback]);
            }
        }

        return $query;
    }

    /**
     * 執行"select"語句並且翻譯結果
     *
     * @param  null|string $locale
     * @param  bool|string $fallbackLocale
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function scopeTranslatedAll($query, $locale = null, $fallbackLocale = true, $columns = ['*'])
    {
        if ($locale === null && self::$transLocale !== null) {
            $locale         = self::$transLocale;
            $fallbackLocale = self::$fallbackLocale;
        }

        return $query->get()->map(
            fn($model) => $model->translated($locale, $fallbackLocale, $columns, $model)
        );
    }

    /**
     * 取得附加關係模型
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $relation 關係名稱
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function getModelByTrans(Builder $query, $relation = null)
    {
        if ($relation === null) {
            return $query->getModel();
        }

        if (Str::contains($relation, '.')) {
            return $this->getModelByTrans($query->getRelation(Str::before($relation, '.'))->getRelated()::query(), Str::after($relation, '.'));
        }

        return $query->getRelation($relation)->getRelated();
    }

    /**
     * 判斷是否可翻譯
     *
     * @param Illuminate\Database\Eloquent\Model $model
     *
     * @return bool
     */
    public function canTrans(Model $model)
    {
        return method_exists($model, 'translations');
    }

    /**
     * 判斷是否為可翻譯欄位
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param array $patterns
     * @param string $column
     *
     * @return bool
     */
    private static function canTransColumn(Model $model, $patterns, $column)
    {
        $table = $model->getTable();

        return (bool) collect($patterns)->first(
            fn($item) => Str::is($item, $column) || Str::is($item, "$table.$column")
        );
    }

    /**
     * 翻譯模型
     *
     * @param  null|string $locale
     * @param  bool|string $fallbackLocale
     * @param  array|string $columns
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|null $model
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Support\Collection<TKey, TMapValue>|static<TKey, TMapValue>
     */
    public function translated($locale = null, $fallbackLocale = true, $columns = ['*'], $model = null)
    {
        static::setTransLocale($locale, $fallbackLocale);

        if ($model instanceof Collection) {
            return $model->map(
                fn($item) => $this->translated(self::$transLocale, self::$fallbackLocale, $columns, $item)
            );
        }

        if ($model === null) {
            $model = $this;
        } elseif (!$model instanceof Model || $model instanceof (Translator::translationModel())) {
            return $model;
        }

        if ($this->canTrans($model) && $model->getLocale() !== self::$transLocale) {
            $translated = $model->translateByModel(self::$transLocale, self::$fallbackLocale, $columns);

            $model->translateByLang(self::$transLocale, self::$fallbackLocale, $columns, $translated);
        }

        foreach ($model->getRelations() as $relation => $item) {
            $model->setRelation($relation, $this->{__FUNCTION__}(self::$transLocale, self::$fallbackLocale, $columns, $item));
        }

        return $model;
    }

    /**
     * 透過Model翻譯
     *
     * @param  null|string $locale
     * @param  bool|string $fallbackLocale
     * @param  array|string  $columns
     *
     * @return array
     */
    public function translateByModel($locale = null, $fallbackLocale = true, $columns = ['*'])
    {
        static::setTransLocale($locale, $fallbackLocale);

        $translated = [];

        $attributes = $this->attributes;

        $this->translations
            ->whereIn('locale', [self::$transLocale, self::$fallbackLocale])
            ->each(function ($translation) use (&$translated, &$attributes, $columns) {
                $translatedKey = $translation->translated_key;

                if (
                    (self::$transLocale != $translation->locale && self::$fallbackLocale != $translation->locale)
                    || array_key_exists($translatedKey, $translated)
                ) {
                    return true;
                }

                if (static::canTransColumn($this, $columns, $translatedKey)) {
                    $attributes[$translatedKey] = tap($translation->translated_value, function () use (&$translated, $translatedKey) {
                        array_push($translated, $translatedKey);
                    });
                }
            });

        $this->setRawAttributes($attributes);

        $this->makeHidden('translations');

        return $translated;
    }

    /**
     * 透過Lang翻譯
     *
     * @param  null|string $locale
     * @param  bool|string $fallbackLocale
     * @param  array|string  $columns
     * @param  array  $ignore
     *
     * @return array
     */
    public function translateByLang($locale = null, $fallbackLocale = true, $columns = ['*'], $ignore = [])
    {
        static::setTransLocale($locale, $fallbackLocale);

        $translated = [];

        $attributes = collect($this->attributes)->map(function ($value, $key) use (&$translated, $columns, $ignore) {
            if (!in_array($key, $ignore) && static::canTransColumn($this, $columns, $key)) {
                if (Lang::has($value, self::$transLocale)) {
                    return tap(Lang::get($value, [], self::$transLocale), function () use (&$translated, $key) {
                        array_push($translated, $key);
                    });
                }

                if (Lang::has($value, self::$fallbackLocale)) {
                    return tap(Lang::get($value, [], self::$fallbackLocale), function () use (&$translated, $key) {
                        array_push($translated, $key);
                    });
                }
            }

            return $value;
        })->all();

        $this->setRawAttributes($attributes);

        return $translated;
    }
}
