-- Migrate existing annual_fee data from receipt_number to payment_deadline
UPDATE membership_fees
SET annual_fee = (
    SELECT jsonb_agg(
        jsonb_build_object(
            'year', elem->'year',
            'amount', elem->'amount',
            'status', elem->'status',
            'payment_date', elem->'payment_date',
            'payment_method', elem->'payment_method',
            'payment_deadline', COALESCE(elem->'payment_deadline', to_jsonb(NULL)),
            'notes', elem->'notes'
        )
    )
    FROM jsonb_array_elements(annual_fee) AS elem
)
WHERE annual_fee IS NOT NULL AND annual_fee != '[]'::jsonb;