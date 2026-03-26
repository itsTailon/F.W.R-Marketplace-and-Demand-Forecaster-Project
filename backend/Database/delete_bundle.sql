-- Seller deletes a specific bundle --
DELETE FROM bundle
WHERE sellerID = ?
AND bundleID = ?;

-- Seller deletes all bundles with a specified status --
DELETE FROM bundle
WHERE sellerID = ?
AND bundleStatus = ?; -- 'available', 'reserved', 'collected', or 'cancelled'

-- Seller deletes all bundles --
DELETE FROM bundle
WHERE sellerID = ?;