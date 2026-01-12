<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109211446 extends AbstractMigration
{
	public function getDescription(): string
	{
		return '';
	}

	public function up(Schema $schema): void
	{
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE reset_password_requests CHANGE requested_at requested_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL');
		$this->addSql('ALTER TABLE users CHANGE joined_date joined_date DATETIME NOT NULL, CHANGE verified_date verified_date DATETIME DEFAULT NULL');
		$this->addSql('ALTER TABLE wishlists CHANGE uuid uuid BINARY(16) NOT NULL, CHANGE is_deleted is_deleted DATETIME DEFAULT NULL');
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE reset_password_requests CHANGE requested_at requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
		$this->addSql('ALTER TABLE users CHANGE joined_date joined_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE verified_date verified_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
		$this->addSql('ALTER TABLE wishlists CHANGE uuid uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE is_deleted is_deleted DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
	}
}
