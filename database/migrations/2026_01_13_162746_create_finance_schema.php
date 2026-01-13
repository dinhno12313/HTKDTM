<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // =========================
        // USERS + RBAC
        // =========================
        // USERS: add extra fields (do NOT recreate users table)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) $table->string('phone', 30)->nullable();
            if (!Schema::hasColumn('users', 'avatar_url')) $table->string('avatar_url', 500)->nullable();
            if (!Schema::hasColumn('users', 'locale')) $table->string('locale', 10)->default('vi');
            if (!Schema::hasColumn('users', 'timezone')) $table->string('timezone', 50)->default('Asia/Bangkok');

            // SQLite: dùng string cho status để tránh rắc rối enum/check
            if (!Schema::hasColumn('users', 'status')) $table->string('status')->default('active');

            if (!Schema::hasColumn('users', 'last_login_at')) $table->timestamp('last_login_at')->nullable();

            // thêm soft delete nếu bảng users mặc định chưa có
            if (!Schema::hasColumn('users', 'deleted_at')) $table->softDeletes();
        });

        // index status (nếu chưa có)
        Schema::table('users', function (Blueprint $table) {
            // Laravel không có hasIndex mặc định, nên cứ thêm index là ok trong SQLite mới,
            // nếu sợ trùng, bạn có thể bỏ dòng này.
            // $table->index('status');
        });


        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('guard_name', 50)->default('web');
            $table->timestamps();

            $table->unique(['name','guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('guard_name', 50)->default('web');
            $table->timestamps();

            $table->unique(['name','guard_name']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['permission_id','role_id']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('model_type', 150);
            $table->unsignedBigInteger('model_id');

            $table->primary(['role_id','model_id','model_type']);
            $table->index(['model_id','model_type'], 'idx_mhr_model');
        });

        // =========================
        // WALLETS / ACCOUNTS
        // =========================
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->enum('type', ['cash','bank','e_wallet','credit','other'])->default('cash');
            $table->char('currency', 3)->default('VND');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->string('note', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('provider', 120)->nullable();
            $table->string('account_no_masked', 50)->nullable(); // ****1234
            $table->char('currency', 3)->default('VND');
            $table->decimal('current_balance', 18, 2)->default(0);
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('wallet_id');
        });

        // =========================
        // CATEGORIES + TAGS
        // =========================
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 120);
            $table->enum('type', ['income','expense']);
            $table->string('icon', 80)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id','type'], 'idx_categories_user_type');
            $table->index('parent_id');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();

            $table->unique(['user_id','name']);
        });

        // =========================
        // EMAIL IMPORT
        // =========================
        Schema::create('email_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('provider', ['gmail','outlook','imap','other'])->default('gmail');
            $table->string('email', 190);
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->enum('status', ['connected','revoked','error'])->default('connected');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id','email']);
        });

        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_source_id')->constrained('email_sources')->cascadeOnDelete();
            $table->string('provider_message_id', 255);
            $table->string('from_email', 190)->nullable();
            $table->string('subject', 255)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->mediumText('raw_body')->nullable();
            $table->enum('parse_status', ['new','parsed','ignored','failed'])->default('new');
            $table->decimal('parse_confidence', 5, 4)->nullable(); // 0.0000-1.0000
            $table->json('parsed_payload')->nullable();
            $table->timestamps();

            $table->unique(['email_source_id','provider_message_id'], 'uq_email_msg');
            $table->index('parse_status');
            $table->index('received_at');
        });

        // =========================
        // TRANSACTIONS
        // =========================
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            $table->enum('type', ['income','expense','transfer']);
            $table->decimal('amount', 18, 2); // always positive
            $table->char('currency', 3)->default('VND');

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete(); // null for transfer
            $table->string('merchant', 190)->nullable();
            $table->string('note', 500)->nullable();

            $table->timestamp('occurred_at');
            $table->enum('status', ['posted','pending','void'])->default('posted');

            // transfer support
            $table->foreignId('transfer_to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->decimal('transfer_fee', 18, 2)->default(0);

            // email import
            $table->foreignId('email_message_id')->nullable()->constrained('email_messages')->nullOnDelete();

            $table->enum('created_by', ['manual','email','admin'])->default('manual');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id','occurred_at'], 'idx_tx_user_time');
            $table->index(['wallet_id','occurred_at'], 'idx_tx_wallet_time');
            $table->index(['category_id','occurred_at'], 'idx_tx_category_time');
            $table->index('merchant');

            // MySQL 8+ InnoDB supports FULLTEXT
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['note','merchant'], 'ftx_tx_note_merchant');
}

        });

        Schema::create('transaction_tags', function (Blueprint $table) {
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['transaction_id','tag_id']);
        });

        // =========================
        // BUDGETS
        // =========================
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->enum('period', ['weekly','monthly','yearly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_limit', 18, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id','start_date','end_date'], 'idx_budgets_user_date');
        });

        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->decimal('limit_amount', 18, 2);
            $table->timestamps();

            $table->unique(['budget_id','category_id'], 'uq_budget_category');
        });

        // =========================
        // SAVING GOALS
        // =========================
        Schema::create('saving_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->decimal('target_amount', 18, 2);
            $table->decimal('current_amount', 18, 2)->default(0);
            $table->char('currency', 3)->default('VND');
            $table->date('due_date')->nullable();
            $table->enum('status', ['active','done','paused'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id','status'], 'idx_goals_user_status');
        });

        // =========================
        // DEBTS
        // =========================
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->decimal('principal', 18, 2);
            $table->decimal('balance', 18, 2);
            $table->char('currency', 3)->default('VND');
            $table->decimal('interest_rate', 6, 3)->nullable(); // %/year
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('note', 500)->nullable();
            $table->enum('status', ['active','closed'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id','status'], 'idx_debts_user_status');
        });

        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamp('paid_at');
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->index(['debt_id','paid_at'], 'idx_debt_payments_debt');
        });

        // =========================
        // STUDENT VERIFICATION + PLANS + SUBSCRIPTIONS + PAYMENTS
        // =========================
        Schema::create('student_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete(); // admin
            $table->string('school', 190)->nullable();
            $table->string('student_code', 80)->nullable();
            $table->string('proof_image_url', 500)->nullable();
            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->string('note', 300)->nullable();
            $table->timestamps();

            $table->index(['user_id','status'], 'idx_sv_user_status');
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->decimal('price', 18, 2);
            $table->char('currency', 3)->default('VND');
            $table->enum('billing_period', ['monthly','yearly','lifetime'])->default('monthly');
            $table->decimal('student_discount_percent', 5, 2)->default(15.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->enum('status', ['active','canceled','expired','pending'])->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id','status'], 'idx_sub_user_status');
        });

        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->enum('provider', ['payos','other'])->default('payos');
            $table->string('provider_order_id', 120)->nullable();
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3)->default('VND');
            $table->enum('status', ['created','paid','failed','canceled','expired'])->default('created');
            $table->string('checkout_url', 600)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['user_id','status'], 'idx_pay_user_status');
        });

        // =========================
        // CHATBOT LOGS
        // =========================
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('channel', ['web','zalo','messenger','other'])->default('web');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id','started_at'], 'idx_chat_sessions_user');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->enum('role', ['user','assistant','system']);
            $table->mediumText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['session_id','created_at'], 'idx_chat_messages_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');

        Schema::dropIfExists('payment_orders');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('student_verifications');

        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('debts');

        Schema::dropIfExists('saving_goals');

        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('budgets');

        Schema::dropIfExists('transaction_tags');
        Schema::dropIfExists('transactions');

        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('email_sources');

        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');

        Schema::dropIfExists('accounts');
        Schema::dropIfExists('wallets');

        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        // Schema::dropIfExists('users');
    }
};
