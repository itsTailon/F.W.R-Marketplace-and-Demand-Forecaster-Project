CREATE DATABASE teamProject; -- can be renamed

CREATE TABLE account ( -- formerly `user`
    userID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(128) NOT NULL UNIQUE,
    passwordHash VARCHAR(256) NOT NULL,
    accountType ENUM('customer', 'seller') NOT NULL, -- new attribute following feedback
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE customer (
    customerID INT NOT NULL PRIMARY KEY,
    username VARCHAR(128) NOT NULL UNIQUE, -- non-identifying name
    FOREIGN KEY (customerID) REFERENCES account(userID) ON DELETE CASCADE
    );

CREATE TABLE seller (
    sellerID INT NOT NULL PRIMARY KEY,
    sellerName VARCHAR(128) NOT NULL UNIQUE, -- formerly `name`
    sellerAddress VARCHAR(256) NOT NULL,
    FOREIGN KEY (sellerID) REFERENCES account(userID) ON DELETE CASCADE
    );

CREATE TABLE bundle (
    bundleID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bundleStatus ENUM('draft', 'available', 'reserved', 'collected', 'expired') NOT NULL,
    sellerID INT NOT NULL,
    title VARCHAR(128) NOT NULL, -- formerly `name`
    details TEXT NOT NULL, -- formerly `description`
    category ENUM('groceries', 'sandwiches', 'meals', 'sweet_pastries', 'savoury_pastries', 'cakes', 'brownies') NULL, 
    imageURL VARCHAR (256) NULL,
    rrp DECIMAL(8, 2) NOT NULL, -- recommended retail price
    discountedPrice DECIMAL(8, 2) NOT NULL,
    validFrom DATETIME NULL,
    validUntil DATETIME NULL,
    purchaserID INT DEFAULT NULL,
    CHECK (rrp > discountedPrice), -- the discounted price should be less than the retail price
    CHECK (validUntil > validFrom),
    FOREIGN KEY (sellerID) REFERENCES seller(sellerID) ON DELETE CASCADE,
    FOREIGN KEY (purchaserID) REFERENCES customer(customerID) ON DELETE CASCADE
    );

CREATE TABLE allergen (
    allergenName VARCHAR (64) NOT NULL PRIMARY KEY
);

CREATE TABLE bundle_allergen (
    bundleID INT NOT NULL,
    allergenName VARCHAR (64) NOT NULL,
    PRIMARY KEY (bundleID, allergenName),
    FOREIGN KEY (bundleID) REFERENCES bundle(bundleID) ON DELETE CASCADE,
    FOREIGN KEY (allergenName) REFERENCES allergen(allergenName) ON DELETE CASCADE
);

CREATE TABLE reservation (
    reservationID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bundleID INT NOT NULL,
    purchaserID INT NOT NULL,
    reservationStatus ENUM ('active', 'completed', 'no-show', 'cancelled') NOT NULL,
    claimCode VARCHAR (16) NOT NULL UNIQUE,
    FOREIGN KEY (bundleID) REFERENCES bundle (bundleID) ON DELETE CASCADE,
    FOREIGN KEY (purchaserID) REFERENCES customer (customerID) ON DELETE CASCADE
);

CREATE TABLE issue (
    issueID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customerID INT NOT NULL,
    bundleID INT NOT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolvedAt DATETIME DEFAULT NULL,
    issueDescription TEXT NOT NULL,
    sellerResponse TEXT,
    issueStatus ENUM ('ongoing', 'resolved') NOT NULL,
    FOREIGN KEY (customerID) REFERENCES customer (customerID) ON DELETE CASCADE,
    FOREIGN KEY (bundleID) REFERENCES bundle (bundleID) ON DELETE CASCADE
);

CREATE TABLE streak (
    streakID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customerID INT NOT NULL, 
    streakStatus ENUM ('active', 'inactive') NOT NULL,
    startDate DATETIME NOT NULL,
    endDate DATETIME DEFAULT NULL,
    FOREIGN KEY (customerID) REFERENCES customer (customerID) ON DELETE CASCADE
);