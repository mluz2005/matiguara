<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN: informes de «Atención numérica» redactados con ayuda de la IA (Etapa 3)
 * -------------------------------------------------------------------------
 * Un informe por cada vez que la docente lo pide (queda el historial; se muestra el último).
 *
 *  lectura        qué se observa en los datos (para la docente)
 *  actividades    2 o 3 ideas para el aula
 *  texto_familia  borrador de texto para conversar con la familia (la docente lo revisa y lo adapta)
 *  origen         'ia' (lo redactó la IA) | 'plantilla' (texto fijo de respaldo)
 *  motivo_respaldo por qué se usó la plantilla (sin conexión, respuesta inválida, frase no permitida…)
 *  datos_enviados exactamente lo que se mandó a la IA (anónimo): sirve para auditar la privacidad (RN-01)
 *
 * No es un diagnóstico: es un borrador de apoyo. Se borra en cascada con el niño (RF-21).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tamizaje_informes')) {
            return;
        }

        Schema::create('tamizaje_informes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->nullOnDelete();
            $table->string('id_anonimizado', 64);                          // UUID: lo único que identifica al niño hacia afuera
            $table->string('semaforo', 10)->nullable();                    // color al momento de pedir el informe
            $table->unsignedSmallInteger('ciclos')->default(0);
            $table->text('lectura');
            $table->text('actividades');
            $table->text('texto_familia');
            $table->string('origen', 12)->default('plantilla');            // ia | plantilla
            $table->string('motivo_respaldo', 120)->nullable();
            $table->longText('datos_enviados')->nullable();                // JSON anónimo enviado a la IA
            $table->unsignedInteger('tokens_usados')->nullable();
            $table->timestamp('generado_en')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'generado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tamizaje_informes');
    }
};
