<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a "estudiantes" el correo del padre, madre o tutor.
 *
 * Lo carga la docente al crear la cuenta del niño (o después, si no lo tenía).
 * Con ese correo + el código de acceso del niño, la familia entra a ver el
 * avance de su hijo/a (pantalla "Acceso para familias").
 *
 * Es opcional (puede quedar vacío) y no toca ningún dato existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('estudiantes', 'email_tutor')) {
            Schema::table('estudiantes', function (Blueprint $table) {
                $table->string('email_tutor', 191)->nullable()->after('codigo_acceso')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('estudiantes', 'email_tutor')) {
            Schema::table('estudiantes', function (Blueprint $table) {
                $table->dropIndex(['email_tutor']);
                $table->dropColumn('email_tutor');
            });
        }
    }
};
