<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260112185624 extends AbstractMigration
{
	public function getDescription(): string
	{
		return 'Initial migration';
	}

	public function up(Schema $schema): void
	{
		$this->addSql('CREATE TABLE refresh_token (refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, UNIQUE INDEX uniq_c74f2195c74f2195 (refresh_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX idx_7ce748aa76ed395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, joined_date DATETIME NOT NULL, verified_date DATETIME DEFAULT NULL, last_login DATETIME DEFAULT NULL, is_staff TINYINT DEFAULT 0 NOT NULL, UNIQUE INDEX uniq_8d93d649e7927c74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('CREATE TABLE verification_token (id INT AUTO_INCREMENT NOT NULL, hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX uniq_c1cc006bd1b862b8 (hash), INDEX idx_c1cc006ba76ed395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('CREATE TABLE wishlist (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, is_deleted DATETIME DEFAULT NULL, access_code VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, INDEX idx_9ce12a31a76ed395 (user_id), UNIQUE INDEX uniq_wishlist_uuid (uuid), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('CREATE TABLE wishlist_item (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, priority SMALLINT DEFAULT 1 NOT NULL, hidden TINYINT DEFAULT 0 NOT NULL, user_id INT NOT NULL, wishlist_id INT NOT NULL, INDEX idx_6424f4e8a76ed395 (user_id), INDEX idx_6424f4e8fb8e54cd (wishlist_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
		$this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT fk_7ce748aa76ed395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE verification_token ADD CONSTRAINT fk_c1cc006ba76ed395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE wishlist ADD CONSTRAINT fk_9ce12a31a76ed395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT fk_6424f4e8a76ed395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT fk_6424f4e8fb8e54cd FOREIGN KEY (wishlist_id) REFERENCES wishlist (id) ON DELETE CASCADE');
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY fk_7ce748aa76ed395');
		$this->addSql('ALTER TABLE verification_token DROP FOREIGN KEY fk_c1cc006ba76ed395');
		$this->addSql('ALTER TABLE wishlist DROP FOREIGN KEY fk_9ce12a31a76ed395');
		$this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY fk_6424f4e8a76ed395');
		$this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY fk_6424f4e8fb8e54cd');
		$this->addSql('DROP TABLE refresh_token');
		$this->addSql('DROP TABLE reset_password_request');
		$this->addSql('DROP TABLE user');
		$this->addSql('DROP TABLE verification_token');
		$this->addSql('DROP TABLE wishlist');
		$this->addSql('DROP TABLE wishlist_item');
	}
}
