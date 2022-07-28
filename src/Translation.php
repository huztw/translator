<?php

namespace Huztw\Translator;

use Illuminate\Database\Eloquent\Model;

/**
 * 翻譯
 *
 * @property int $id Id
 * @property string $locale 語系
 * @property string $translatable_id 可翻譯主鍵
 * @property string $translatable_type 可翻譯類型
 * @property string $translated_key 翻譯的鍵
 * @property string $translated_value 翻譯的值
 * @property \Illuminate\Support\Carbon|null $created_at 建立時間
 * @property \Illuminate\Support\Carbon|null $updated_at 更新時間
 *
 * @property-read \Illuminate\Database\Eloquent\Model|mixed $translatable 可翻譯的Model
 * @mixin \Eloquent
 */
class Translation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translations';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the parent translatable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function translatable()
    {
        return $this->morphTo(__FUNCTION__);
    }
}
