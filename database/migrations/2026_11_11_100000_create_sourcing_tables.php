<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sourcing_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 20)->unique();             // CS-2026-00042
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Guest contact (also kept for logged-in buyers as the snapshot at submission).
            $table->string('contact_name');
            $table->string('contact_email')->index();
            $table->string('company_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('locale', 10);
            // Product
            $table->string('title');
            $table->text('description');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('specifications')->nullable();
            $table->string('materials')->nullable();
            $table->string('customization', 20)->default('none'); // none | logo | oem | odm | unsure
            $table->json('required_certifications')->nullable();
            // Quantities & budget
            $table->unsignedInteger('quantity');
            $table->string('quantity_unit', 30)->nullable();
            $table->unsignedInteger('target_price_cents')->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->date('desired_delivery_date')->nullable();
            // Delivery
            $table->char('destination_country', 2)->default('NL');
            $table->string('incoterm', 10)->nullable();          // null = "not sure, advise me"
            $table->text('additional_requirements')->nullable();
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            // Workflow
            $table->string('status', 30)->default('submitted')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'assigned_to']);
        });

        Schema::create('sourcing_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_request_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sourcing_request_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('suggested')->index();
            // Quote summary as received from the supplier (indicative, not a contract).
            $table->unsignedInteger('unit_price_cents')->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedInteger('moq')->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->unsignedInteger('sample_cost_cents')->nullable();
            $table->string('incoterm', 10)->nullable();
            $table->string('payment_terms')->nullable();
            $table->date('quote_valid_until')->nullable();
            $table->text('advice')->nullable();          // ChinaSupply's note, visible to the buyer when shared
            $table->text('internal_notes')->nullable();  // staff only
            $table->boolean('shared_with_buyer')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['sourcing_request_id', 'supplier_id']);
        });

        Schema::create('sourcing_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_request_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->json('data')->nullable();
            $table->boolean('visible_to_buyer')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sourcing_request_id', 'created_at']);
        });

        Schema::create('sourcing_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('from_staff');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['sourcing_request_id', 'created_at']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'supplier_id']);
        });

        // Directory search (MySQL FULLTEXT). Skipped on other drivers.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('suppliers', fn (Blueprint $table) => $table->fullText(['name', 'name_local'], 'suppliers_name_fulltext'));
            Schema::table('supplier_translations', fn (Blueprint $table) => $table->fullText(['description', 'capabilities'], 'supplier_translations_fulltext'));
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('supplier_translations', fn (Blueprint $table) => $table->dropFullText('supplier_translations_fulltext'));
            Schema::table('suppliers', fn (Blueprint $table) => $table->dropFullText('suppliers_name_fulltext'));
        }
        foreach (['favorites', 'sourcing_request_messages', 'sourcing_request_events', 'sourcing_request_matches', 'sourcing_request_files', 'sourcing_requests'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
