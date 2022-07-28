<?php

namespace Huztw\Translator;

class Translator
{
    /**
     * The translation model class name.
     *
     * @var string
     */
    public static $translationModel = 'Huztw\Translator\Translation';

    /**
     * Set the translation model class name.
     *
     * @param  string  $translationModel
     * @return void
     */
    public static function useTranslationModel($translationModel)
    {
        static::$translationModel = $translationModel;
    }

    /**
     * Get the translation model class name.
     *
     * @return string
     */
    public static function translationModel()
    {
        return static::$translationModel;
    }

    /**
     * Get a new translation model instance.
     *
     * @return \Huztw\Translator\Translation
     */
    public static function translation()
    {
        return new static::$translationModel;
    }
}
