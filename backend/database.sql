Create database research_portal;
Use research_portal;
Create table users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name varchar(255) not null,
    role varchar(50) NOT null,
    department varchar(255) not null,
    email varchar(255) not null unique,
    password varchar(255) not null,
    cv_path varchar(255) not null,
    created_at Timestamp DEFAULT CURRENT_TIMESTAMP);