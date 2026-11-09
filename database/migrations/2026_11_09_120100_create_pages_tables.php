<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CMS pages managed in Filament: legal pages, service descriptions, about, etc.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique(); // stable identifier used by code, e.g. "privacy"
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('requires_legal_review')->default(false);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->longText('body')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 30)->index();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('message');
            $table->string('locale', 10);
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
    }
};
