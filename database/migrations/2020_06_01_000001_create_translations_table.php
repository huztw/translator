<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database schema.
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * Create a new migration instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->schema = Schema::connection($this->getConnection());
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type')->index();
            $table->string('translatable_id')->index();
            $table->string('locale', 50)->index();
            $table->string('translated_key', 50)->index();
            $table->text('translated_value');

            $table->unique(['translatable_type', 'translatable_id', 'locale', 'translated_key'], 'translations_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->dropIfExists('translations');
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return config('translator.storage.database.connection');
    }
};
