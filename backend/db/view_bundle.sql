-- Seller views a specific bundle --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice -- sellerID and purchaserID would be redundant here
FROM bundle
WHERE sellerID = ?
AND bundleID = ?;

-- Seller views all bundles by ascending bundleID --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
ORDER BY bundleID ASC;

-- Seller views all bundles by descending bundleID --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
ORDER BY bundleID DESC;

-- Seller views all bundles in alphabetical order --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
ORDER BY title ASC;

-- Seller views all bundles in reverse alphabetical order --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
ORDER BY title DESC;

-- Seller views all bundles from cheapest to most expensive --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
ORDER BY discountedPrice ASC;

-- Seller views all bundles from most expensive to cheapest --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
ORDER BY discountedPrice DESC;

-- Seller views all bundles with a specified title --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
AND title = ?
ORDER BY title ASC;

-- Seller views all bundles with a specified status --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
AND bundleStatus = ? -- 'available', 'reserved', 'collected', or 'cancelled'
ORDER BY title ASC;

-- Seller views all bundles with a title containing a specified keyword --
SELECT bundleID, title, details, bundleStatus, rrp, discountedPrice
FROM bundle
WHERE sellerID = ?
AND title LIKE CONCAT('%', ?, '%')
ORDER BY title ASC;