-- Удаление таблиц, если они существуют (для пересоздания)
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;

-- Создание таблицы категорий с улучшениями
CREATE TABLE categories (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(100) NOT NULL UNIQUE,
                            description TEXT,
                            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Создание таблицы продуктов с проверками
CREATE TABLE products (
                          id SERIAL PRIMARY KEY,
                          name VARCHAR(100) NOT NULL,
                          category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
                          price DECIMAL(10, 2) NOT NULL CHECK (price > 0),
                          stock INTEGER NOT NULL CHECK (stock >= 0),
                          sku VARCHAR(50) UNIQUE,
                          created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                          updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                          CONSTRAINT valid_product CHECK (name !~ '^\s*$')
    );

-- Создание таблицы заказов с индексами
CREATE TABLE orders (
                        id SERIAL PRIMARY KEY,
                        product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
                        quantity INTEGER NOT NULL CHECK (quantity > 0),
                        order_time TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        customer_id VARCHAR(50),
                        status VARCHAR(20) DEFAULT 'completed' CHECK (status IN ('pending', 'completed', 'cancelled')),
                        INDEX idx_order_time (order_time),
                        INDEX idx_product_id (product_id)
);

-- Триггер для обновления временных меток
CREATE OR REPLACE FUNCTION update_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Применение триггеров к таблицам
CREATE TRIGGER update_categories_timestamp
    BEFORE UPDATE ON categories
    FOR EACH ROW EXECUTE FUNCTION update_timestamp();

CREATE TRIGGER update_products_timestamp
    BEFORE UPDATE ON products
    FOR EACH ROW EXECUTE FUNCTION update_timestamp();

-- Заполнение тестовыми данными с транзакцией
BEGIN;

INSERT INTO categories (name, description) VALUES
                                               ('Электроника', 'Гаджеты и устройства'),
                                               ('Одежда', 'Мужская и женская одежда'),
                                               ('Бытовая техника', 'Техника для дома');

INSERT INTO products (name, category_id, price, stock, sku) VALUES
                                                                ('Смартфон X10', 1, 29999.99, 100, 'ELEC-SM-X10'),
                                                                ('Ноутбук Pro', 1, 59999.99, 50, 'ELEC-NB-PRO'),
                                                                ('Футболка Classic', 2, 1999.99, 200, 'CLOTH-TS-CL'),
                                                                ('Джинсы Premium', 2, 3999.99, 150, 'CLOTH-JN-PR'),
                                                                ('Холодильник', 3, 25999.99, 30, 'HOME-REF-01');

-- Генерация тестовых заказов
DO $$
DECLARE
product RECORD;
    i INTEGER;
BEGIN
FOR product IN SELECT id FROM products LOOP
    FOR i IN 1..5 LOOP
               INSERT INTO orders (product_id, quantity, customer_id)
               VALUES (product.id, (random() * 5 + 1)::INTEGER, 'cust-' || (random() * 1000)::INTEGER);
END LOOP;
END LOOP;
END $$;

COMMIT;

-- Создание представления для аналитики
CREATE VIEW sales_analytics AS
SELECT
    c.name AS category,
    COUNT(o.id) AS total_orders,
    SUM(o.quantity) AS total_items,
    SUM(o.quantity * p.price) AS total_revenue,
    AVG(o.quantity) AS avg_order_size
FROM orders o
         JOIN products p ON o.product_id = p.id
         JOIN categories c ON p.category_id = c.id
GROUP BY c.id, c.name;

-- Создание индексов для часто используемых запросов
CREATE INDEX idx_category_name ON categories(name);
CREATE INDEX idx_product_category ON products(category_id);