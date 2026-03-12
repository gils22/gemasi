<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('edisi_lomba')) {
            Schema::create('edisi_lomba', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->unsignedSmallInteger('tahun')->unique();
                $table->string('status')->default('draft');
                $table->boolean('aktif')->default(false);
                $table->timestamp('mulai_pada')->nullable();
                $table->timestamp('selesai_pada')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('edisi_lomba_user_role')) {
            Schema::create('edisi_lomba_user_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('edisi_lomba_id')->constrained('edisi_lomba')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['edisi_lomba_id', 'user_id', 'role_id'], 'edisi_user_role_unik');
                $table->index(['edisi_lomba_id', 'role_id'], 'edisi_role_idx');
            });
        }

        if (Schema::hasTable('editions')) {
            $oldEditions = DB::table('editions')->get();
            foreach ($oldEditions as $old) {
                DB::table('edisi_lomba')->updateOrInsert(
                    ['id' => $old->id],
                    [
                        'nama' => $old->name,
                        'tahun' => $old->year,
                        'status' => $old->status === 'archived' ? 'arsip' : ($old->status === 'active' ? 'aktif' : $old->status),
                        'aktif' => (bool) $old->is_active,
                        'mulai_pada' => $old->start_at,
                        'selesai_pada' => $old->end_at,
                        'created_at' => $old->created_at,
                        'updated_at' => $old->updated_at,
                    ]
                );
            }
        }

        if (Schema::hasTable('edition_user_role')) {
            $oldPivotRows = DB::table('edition_user_role')->get();
            foreach ($oldPivotRows as $old) {
                DB::table('edisi_lomba_user_role')->updateOrInsert(
                    [
                        'edisi_lomba_id' => $old->edition_id,
                        'user_id' => $old->user_id,
                        'role_id' => $old->role_id,
                    ],
                    [
                        'created_at' => $old->created_at ?? now(),
                        'updated_at' => $old->updated_at ?? now(),
                    ]
                );
            }
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('edition_user_role');
        Schema::dropIfExists('editions');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (!Schema::hasTable('editions')) {
            Schema::create('editions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedSmallInteger('year')->unique();
                $table->string('status')->default('draft');
                $table->boolean('is_active')->default(false);
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('edition_user_role')) {
            Schema::create('edition_user_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['edition_id', 'user_id', 'role_id']);
                $table->index(['edition_id', 'role_id']);
            });
        }

        if (Schema::hasTable('edisi_lomba')) {
            $edisiRows = DB::table('edisi_lomba')->get();
            foreach ($edisiRows as $row) {
                DB::table('editions')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->nama,
                        'year' => $row->tahun,
                        'status' => $row->status === 'arsip' ? 'archived' : ($row->status === 'aktif' ? 'active' : $row->status),
                        'is_active' => (bool) $row->aktif,
                        'start_at' => $row->mulai_pada,
                        'end_at' => $row->selesai_pada,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]
                );
            }
        }

        if (Schema::hasTable('edisi_lomba_user_role')) {
            $pivotRows = DB::table('edisi_lomba_user_role')->get();
            foreach ($pivotRows as $row) {
                DB::table('edition_user_role')->updateOrInsert(
                    [
                        'edition_id' => $row->edisi_lomba_id,
                        'user_id' => $row->user_id,
                        'role_id' => $row->role_id,
                    ],
                    [
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]
                );
            }
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('edisi_lomba_user_role');
        Schema::dropIfExists('edisi_lomba');
        Schema::enableForeignKeyConstraints();
    }
};
