<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing function if it exists
        DB::statement('DROP FUNCTION IF EXISTS get_daily_balances_for_account(integer[], integer, integer, integer[])');
        
        // Create the updated function that works with the new account structure
        DB::statement('
            CREATE OR REPLACE FUNCTION get_daily_balances_for_account(
                p_account_ids integer[],
                p_offset integer,
                p_limit integer,
                p_invert_account_ids integer[]
            )
            RETURNS json
            LANGUAGE plpgsql
            AS $$
            DECLARE
                start_date date;
                end_date date;
            BEGIN
                -- Get the date range from the transactions
                SELECT 
                    MIN(tj.date::date) as start_date,
                    MAX(tj.date::date) as end_date
                INTO start_date, end_date
                FROM public.transactions t
                JOIN public.transaction_journals tj ON t.transaction_journal_id = tj.id
                WHERE t.account_id = ANY(p_account_ids)
                AND tj.deleted_at IS NULL;
                
                -- If no transactions found, return NULL (same as original function)
                IF start_date IS NULL OR end_date IS NULL THEN
                    RETURN NULL;
                END IF;
                
                -- Return the daily balances using the new account structure
                RETURN (
                    WITH account_transactions AS (
                        SELECT 
                            tj.date::date AS transaction_date,
                            t.account_id,
                            t.amount * CASE 
                                WHEN t.account_id = ANY(p_invert_account_ids) THEN -1 
                                ELSE 1 
                            END AS adjusted_amount
                        FROM public.transactions t
                        JOIN public.transaction_journals tj ON t.transaction_journal_id = tj.id
                        WHERE t.account_id = ANY(p_account_ids)
                        AND tj.deleted_at IS NULL
                        -- Exclude type 15 transactions EXCEPT "Qualified Dividend Reinvestment"
                        AND NOT EXISTS (
                            SELECT 1
                            FROM public.transactions t2
                            JOIN public.accounts a ON t2.account_id = a.id
                            WHERE t2.transaction_journal_id = tj.id
                            AND t2.id != t.id
                            AND tj.description NOT LIKE \'Reinvest Dividend%\'
                            AND (
                                (t.account_id = ANY(p_invert_account_ids) AND a.category_id = 1 AND a.behavior_id = 1) -- Asset/Simple (equivalent to old type 15)
                                OR (a.id = ANY(p_invert_account_ids) AND EXISTS (
                                    SELECT 1
                                    FROM public.accounts a2
                                    WHERE a2.id = t.account_id
                                    AND a2.category_id = 1 AND a2.behavior_id = 1 -- Asset/Simple (equivalent to old type 15)
                                ))
                            )
                        )
                    ),
                    -- Second CTE: Calculate the sum of transactions for each day
                    daily_totals AS (
                        SELECT 
                            transaction_date,
                            SUM(adjusted_amount) as daily_sum
                        FROM account_transactions
                        GROUP BY transaction_date
                    ),
                    -- Third CTE: Generate a series of dates and join with daily_totals
                    date_series AS (
                        SELECT generate_series(start_date, end_date, \'1 day\'::interval)::date AS transaction_date
                    ),
                    -- Fourth CTE: Calculate running totals over time with pagination
                    running_totals AS (
                        SELECT 
                            ds.transaction_date,
                            COALESCE(
                                CASE 
                                    WHEN p_invert_account_ids = \'{}\' THEN 
                                        SUM(dt.daily_sum) OVER (ORDER BY ds.transaction_date)
                                    ELSE 
                                        -SUM(dt.daily_sum) OVER (ORDER BY ds.transaction_date)
                                END,
                                0
                            ) as running_total
                        FROM date_series ds
                        LEFT JOIN daily_totals dt ON ds.transaction_date = dt.transaction_date
                        ORDER BY ds.transaction_date
                        OFFSET p_offset
                        LIMIT p_limit
                    )
                    -- Aggregate results into a JSON object with date as key and balance details as value
                    SELECT json_object_agg(
                        transaction_date,
                        json_build_object(
                            \'balance\', to_json(running_total::text),
                            \'USD\', to_json(running_total::text)
                        )
                    )
                    FROM running_totals
                );
            END;
            $$;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the function
        DB::statement('DROP FUNCTION IF EXISTS get_daily_balances_for_account(integer[], integer, integer, integer[])');
    }
};
