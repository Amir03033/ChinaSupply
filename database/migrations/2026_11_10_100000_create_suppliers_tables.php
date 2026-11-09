<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            // Identity
            $table->string('name');                       // trading/display name (Latin script)
            $table->string('name_local')->nullable();     // name in Chinese characters
            $table->string('legal_name')->nullable();
            $table->string('registration_number', 64)->nullable()->unique(); // e.g. USCC (18 chars)
            $table->string('company_type', 30)->default('unknown')->index();
            $table->char('country', 2)->default('CN');
            $table->string('province', 100)->nullable()->index();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();          // private
            $table->string('website')->nullable();
            $table->string('business_email')->nullable(); // private unless show_contact_publicly
            $table->string('business_phone', 50)->nullable();
            $table->boolean('show_contact_publicly')->default(false);
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('employee_range', 20)->nullable();
            // Capabilities
            $table->unsignedInteger('moq_min')->nullable();
            $table->string('moq_unit', 30)->nullable();
            $table->string('production_capacity')->nullable();
            $table->unsignedSmallInteger('lead_time_min_days')->nullable();
            $table->unsignedSmallInteger('lead_time_max_days')->nullable();
            $table->boolean('offers_oem')->default(false)->index();
            $table->boolean('offers_odm')->default(false)->index();
            $table->boolean('offers_private_label')->default(false);
            $table->json('export_markets')->nullable();
            $table->json('languages')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            // Verification (derived — written only by VerificationLevelCalculator)
            $table->unsignedTinyInteger('verification_level')->default(0)->index();
            $table->boolean('verification_review_required')->default(false);
            $table->timestamp('last_verified_at')->nullable();
            // CRM
            $table->string('pipeline_status', 30)->default('discovered')->index();
            $table->string('source', 30)->default('other')->index();
            $table->string('source_url', 500)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->string('rejection_reason', 500)->nullable();
            // Onboarding
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->timestamp('onboarding_submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pipeline_status', 'verification_level']);
        });

        Schema::create('supplier_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->text('description')->nullable();
            $table->text('capabilities')->nullable();
            $table->timestamps();
            $table->unique(['supplier_id', 'locale']);
        });

        Schema::create('supplier_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('owner');
            $table->timestamps();
            $table->unique(['supplier_id', 'user_id']);
            $table->unique('user_id'); // one supplier per user account
        });

        Schema::create('category_supplier', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['category_id', 'supplier_id']);
            $table->index('supplier_id');
        });

        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('wechat_id', 100)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('preferred_language', 10)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->index();
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->char('sha256', 64)->index();
            $table->string('status', 20)->default('pending')->index();
            $table->date('valid_until')->nullable()->index();
            $table->boolean('is_public')->default(false); // only factory photos may be made public
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('supplier_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certification_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('certificate_number', 120)->nullable();
            $table->string('issued_by')->nullable();
            $table->date('valid_until')->nullable()->index();
            $table->foreignId('document_id')->nullable()->constrained('supplier_documents')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending | verified | rejected
            $table->timestamps();
        });

        Schema::create('verification_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('status', 20)->default('not_checked');
            $table->string('method', 500)->nullable();        // shown publicly: how it was checked
            $table->text('internal_notes')->nullable();       // never public
            $table->foreignId('evidence_document_id')->nullable()->constrained('supplier_documents')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['supplier_id', 'type']); // one current row per type; history in activity_log
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('moq')->nullable();
            $table->string('moq_unit', 30)->nullable();
            $table->unsignedInteger('price_min_cents')->nullable();
            $table->unsignedInteger('price_max_cents')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->boolean('customizable')->default(false);
            $table->string('image_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['supplier_id', 'slug']);
            $table->index(['category_id', 'is_published']);
        });

        Schema::create('outreach_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel', 30)->default('email');
            $table->string('locale', 10)->default('en');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('supplier_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('supplier_contacts')->nullOnDelete();
            $table->foreignId('outreach_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 30);
            $table->string('direction', 10); // outbound | inbound
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['supplier_id', 'occurred_at']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->morphs('taskable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_to', 'completed_at', 'due_at']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['notes', 'tasks', 'supplier_communications', 'outreach_templates', 'products', 'verification_checks',
            'supplier_certifications', 'certifications', 'supplier_documents', 'supplier_contacts', 'category_supplier',
            'supplier_users', 'supplier_translations', 'suppliers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
