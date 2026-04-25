<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the baseline schema.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql(<<<'SQL'
CREATE TABLE users (
    id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT chk_users_status CHECK (status IN ('active', 'blocked')),
    CONSTRAINT uniq_users_email UNIQUE (email)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE product_categories (
    id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(email)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE feedback (
    id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE accounts (
    id UUID NOT NULL,
    user_id UUID NOT NULL,
    device VARCHAR(20) NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    last_login_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT chk_accounts_device CHECK (device IN ('web', 'android', 'ios')),
    CONSTRAINT fk_accounts_user_id FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT uniq_accounts_user_device_device_id UNIQUE (user_id, device, device_id)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE products (
    id UUID NOT NULL,
    category_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT chk_products_unit CHECK (unit IN ('thing', 'package', 'kg')),
    CONSTRAINT fk_products_category_id FOREIGN KEY (category_id) REFERENCES product_categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE
)
SQL);
        $this->addSql('CREATE INDEX idx_products_category_id ON products (category_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE lists (
    id UUID NOT NULL,
    owner_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    is_template BOOLEAN NOT NULL DEFAULT FALSE,
    type VARCHAR(20) NOT NULL,
    touched_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    short_url VARCHAR(255) NOT NULL,
    access INT NOT NULL DEFAULT 1,
    deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT chk_lists_type CHECK (type IN ('shopping', 'todo', 'wishlist')),
    CONSTRAINT fk_lists_owner_id FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT uniq_lists_short_url UNIQUE (short_url)
)
SQL);
        $this->addSql('CREATE INDEX idx_lists_owner_id ON lists (owner_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE list_users (
    list_id UUID NOT NULL,
    user_id UUID NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(list_id, user_id),
    CONSTRAINT fk_list_users_list_id FOREIGN KEY (list_id) REFERENCES lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT fk_list_users_user_id FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
)
SQL);
        $this->addSql('CREATE INDEX idx_list_users_user_id ON list_users (user_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE list_invites (
    list_id UUID NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(list_id, email),
    CONSTRAINT fk_list_invites_list_id FOREIGN KEY (list_id) REFERENCES lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE list_items (
    id UUID NOT NULL,
    list_id UUID NOT NULL,
    user_id UUID NOT NULL,
    product_id UUID DEFAULT NULL,
    completed_user_id UUID DEFAULT NULL,
    name VARCHAR(255) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    version INT NOT NULL DEFAULT 1,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    data JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_list_items_completed_user_id FOREIGN KEY (completed_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT fk_list_items_list_id FOREIGN KEY (list_id) REFERENCES lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT fk_list_items_product_id FOREIGN KEY (product_id) REFERENCES products (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT fk_list_items_user_id FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
)
SQL);
        $this->addSql('CREATE INDEX idx_list_items_list_id ON list_items (list_id)');
        $this->addSql('CREATE INDEX idx_list_items_user_id ON list_items (user_id)');
        $this->addSql('CREATE INDEX idx_list_items_product_id ON list_items (product_id)');
        $this->addSql('CREATE INDEX idx_list_items_completed_user_id ON list_items (completed_user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('DROP TABLE list_items');
        $this->addSql('DROP TABLE list_invites');
        $this->addSql('DROP TABLE list_users');
        $this->addSql('DROP TABLE lists');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE accounts');
        $this->addSql('DROP TABLE product_categories');
        $this->addSql('DROP TABLE users');
    }
}
