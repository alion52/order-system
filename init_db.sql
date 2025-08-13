CREATE TABLE categories (
                            id SERIAL PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            description TEXT
);

CREATE TABLE products (
                          id SERIAL PRIMARY KEY,
                          name VARCHAR(100) NOT NULL,
                          category_id INTEGER REFERENCES categories(id),
                          price DECIMAL(10,2) NOT NULL,
                          stock INTEGER NOT NULL
);

CREATE TABLE orders (
                        id SERIAL PRIMARY KEY,
                        product_id INTEGER REFERENCES products(id),
                        quantity INTEGER NOT NULL,
                        order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE statistics (
                            id SERIAL PRIMARY KEY,
                            category_id INTEGER REFERENCES categories(id),
                            products_sold INTEGER DEFAULT 0,
                            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);