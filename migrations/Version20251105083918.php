<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105083918 extends AbstractMigration
{
	public function getDescription(): string
	{
		return '';
	}

	public function up(Schema $schema): void
	{
		$this->addSql('ALTER TABLE users ADD verified_date DATETIME, DROP is_active');
		$this->addSql('ALTER TABLE wishlists DROP has_password');
	}

	public function down(Schema $schema): void
	{
		$this->addSql('ALTER TABLE users ADD is_active TINYINT(1) DEFAULT 1 NOT NULL, DROP verified_date');
		$this->addSql('ALTER TABLE wishlists ADD has_password TINYINT(1) DEFAULT 0 NOT NULL');
	}
}
