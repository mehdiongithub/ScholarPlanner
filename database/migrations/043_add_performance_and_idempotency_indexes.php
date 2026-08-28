<?php
return [
    'up' => "
        -- 1. Unique index on payment_webhook_logs external_event_id for fast idempotency check & row-level locking
        ALTER TABLE payment_webhook_logs ADD UNIQUE INDEX uq_webhook_event_id (external_event_id);

        -- 2. Composite index on scholarships status and published_at for default sorting & filtering
        ALTER TABLE scholarships ADD INDEX idx_scholarships_status_pub (status, published_at);

        -- 3. Composite index on scholarship_matches user_id and match_score for matched listing sorting
        ALTER TABLE scholarship_matches ADD INDEX idx_matches_user_score (user_id, match_score);

        -- 4. Index on scholarship_applications created_at for pagination sorting
        ALTER TABLE scholarship_applications ADD INDEX idx_applications_created (created_at);
    ",
    'down' => "
        ALTER TABLE payment_webhook_logs DROP INDEX uq_webhook_event_id;
        ALTER TABLE scholarships DROP INDEX idx_scholarships_status_pub;
        ALTER TABLE scholarship_matches DROP INDEX idx_matches_user_score;
        ALTER TABLE scholarship_applications DROP INDEX idx_applications_created;
    "
];
