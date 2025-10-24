<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kedai_kopi', function (Blueprint $table) {
            $table->id();

            // Location Information
            $table->string('kode_kota', 4)->default('2172'); // Tanjungpinang
            $table->string('kode_kecamatan', 3);
            $table->string('kode_kelurahan', 3);
            $table->string('rw', 3)->nullable(); // Optional in model
            $table->string('rt', 3)->nullable();  // Optional in model
            $table->text('alamat');
            $table->decimal('latitude', 9, 7)->nullable(); // decimal:7 in model
            $table->decimal('longitude', 10, 7)->nullable(); // decimal:7 in model

            // Business Information
            $table->string('nama_kedai');
            $table->string('nama_pemilik');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('handphone');
            $table->bigInteger('omzet'); // Stored as integer (rupiah)
            $table->integer('jumlah_pekerja');

            // Stand Information
            $table->integer('stan_sewa')->default(0);
            $table->enum('tren_pekerja', ['naik', 'turun', 'tetap'])->nullable();

            // Additional Information
            $table->text('catatan')->nullable();
            $table->enum('sumber', ['mandiri', 'mitra'])->default('mandiri'); // From model

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['kode_kecamatan', 'kode_kelurahan']);
            $table->index(['nama_kedai']);
            $table->index(['nama_pemilik']);
            $table->index(['sumber']);
            $table->index(['deleted_at']);
            $table->index(['tren_pekerja']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kedai_kopi');
    }
};