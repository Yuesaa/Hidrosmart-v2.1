-- Create notifications table for admin dashboard
CREATE TABLE IF NOT EXISTS notifikasi (
    id_notifikasi INT AUTO_INCREMENT PRIMARY KEY,
    tipe_aktivitas ENUM('contact', 'guarantee', 'order', 'review', 'suggestion') NOT NULL,
    pesan TEXT NOT NULL,
    id_pengguna INT,
    id_referensi INT,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dibaca TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna) ON DELETE CASCADE
);

-- Create triggers for automatic notifications
DELIMITER //

-- Trigger for new orders
CREATE TRIGGER after_order_insert 
AFTER INSERT ON payment 
FOR EACH ROW 
BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.id_pengguna;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('order', CONCAT('Pesanan baru #', NEW.id_order, ' dari ', COALESCE(user_name, 'Unknown')), NEW.id_pengguna, NEW.id_order);
END//

-- Trigger for new contact messages
CREATE TRIGGER after_contact_insert 
AFTER INSERT ON contact 
FOR EACH ROW 
BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.id_pengguna;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('contact', CONCAT('Pesan baru dari ', COALESCE(user_name, 'Unknown'), ': ', NEW.subject), NEW.id_pengguna, NEW.id_saran);
END//

-- Trigger for new guarantee claims
CREATE TRIGGER after_guarantee_insert 
AFTER INSERT ON guarantee 
FOR EACH ROW 
BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.id_pengguna;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('guarantee', CONCAT('Klaim garansi baru dari ', COALESCE(user_name, 'Unknown'), ' untuk order #', NEW.id_order), NEW.id_pengguna, NEW.id_guarantee);
END//

-- Trigger for new reviews
CREATE TRIGGER after_review_insert 
AFTER INSERT ON product_reviews 
FOR EACH ROW 
BEGIN
    DECLARE user_name VARCHAR(255);
    SELECT name INTO user_name FROM pengguna WHERE id_pengguna = NEW.user_id;
    
    INSERT INTO notifikasi (tipe_aktivitas, pesan, id_pengguna, id_referensi) 
    VALUES ('review', CONCAT('Ulasan baru dari ', COALESCE(user_name, 'Unknown'), ' untuk order #', NEW.order_id), NEW.user_id, NEW.id);
END//

DELIMITER ;
