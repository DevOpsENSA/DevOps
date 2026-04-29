<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecoles', function (Blueprint $table) {
            $table->id('idEcole');
            $table->string('nomEcole');
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id('idAdmin');
            $table->string('name')->default('Admin');
            $table->timestamps();
        });

        Schema::create('filieres', function (Blueprint $table) {
            $table->id('idFiliere');
            $table->string('nomFiliere');
            $table->unsignedBigInteger('idEcole');
            $table->timestamps();

            $table->foreign('idEcole')->references('idEcole')->on('ecoles')->cascadeOnDelete();
        });

        Schema::create('semestres', function (Blueprint $table) {
            $table->id('idSemestre');
            $table->unsignedBigInteger('idEcole');
            $table->timestamps();

            $table->foreign('idEcole')->references('idEcole')->on('ecoles')->cascadeOnDelete();
        });

        Schema::create('cours', function (Blueprint $table) {
            $table->id('idCours');
            $table->string('name');
            $table->unsignedBigInteger('idAdmin');
            $table->unsignedBigInteger('idFiliere');
            $table->unsignedBigInteger('idSemestre');
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('idAdmin')->references('idAdmin')->on('admins')->cascadeOnDelete();
            $table->foreign('idFiliere')->references('idFiliere')->on('filieres')->cascadeOnDelete();
            $table->foreign('idSemestre')->references('idSemestre')->on('semestres')->cascadeOnDelete();
        });

        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->timestamps();
        });

        Schema::create('comptes', function (Blueprint $table) {
            $table->string('mail')->primary();
            $table->string('password');
            $table->unsignedBigInteger('idEtudiant');
            $table->enum('status', ['admin', 'student']);
            $table->timestamps();

            $table->foreign('idEtudiant')->references('id')->on('etudiants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cours');
        Schema::dropIfExists('comptes');
        Schema::dropIfExists('etudiants');
        Schema::dropIfExists('semestres');
        Schema::dropIfExists('filieres');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('ecoles');
    }
};
