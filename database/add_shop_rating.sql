-- Add rating column to shops table
ALTER TABLE shops ADD COLUMN IF NOT EXISTS rating DECIMAL(3, 2) DEFAULT 0.0;
ALTER TABLE shops ADD COLUMN IF NOT EXISTS total_reviews INTEGER DEFAULT 0;

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_shops_rating ON shops(rating DESC);
