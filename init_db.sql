-- Создание таблиц
CREATE TABLE categories (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            description TEXT
);

CREATE TABLE products (
                          id SERIAL PRIMARY KEY,
                          name VARCHAR(100) NOT NULL,
                          category_id INTEGER REFERENCES categories(id),
                          price DECIMAL(10, 2) NOT NULL,
                          stock INTEGER NOT NULL
);

CREATE TABLE orders (
                        id SERIAL PRIMARY KEY,
                        product_id INTEGER REFERENCES products(id),
                        quantity INTEGER NOT NULL,
                        order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Заполнение тестовыми данными
INSERT INTO categories (name, description) VALUES
                                               ('Электроника', 'Гаджеты и устройства'),
                                               ('Одежда', 'Мужская и женская одежда');

INSERT INTO products (name, category_id, price, stock) VALUES
                                                           ('Смартфон', 1, 29999.99, 100),
                                                           ('Ноутбук', 1, 59999.99, 50),
                                                           ('Футболка', 2, 1999.99, 200);