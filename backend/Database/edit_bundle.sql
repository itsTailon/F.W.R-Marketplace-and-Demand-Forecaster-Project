-- Seller edits the status of a bundle--
UPDATE bundle
SET bundleStatus = ?
WHERE sellerID = ?
AND bundleID = ?;

-- Seller edits the title of a bundle --
UPDATE bundle
SET title = ?
WHERE sellerID = ?
AND bundleID = ?;

-- Seller edits the details of a bundle --
UPDATE bundle
SET details = ?
WHERE sellerID = ?
AND bundleID = ?;

-- Seller edits the Recommended Retail Price (RRP) of a bundle --
UPDATE bundle
SET rrp = ?
WHERE sellerID = ?
AND bundleID = ?;

-- Seller edits the discounted price of a bundle --
UPDATE bundle
SET discountedPrice = ?
WHERE sellerID = ?
AND bundleID = ?;