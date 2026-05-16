-- Migration: add missing employee Abaredes, Malaya A.
-- This ensures a fresh database contains the record that currently exists in production.
INSERT INTO employees (
    employee_no,
    last_name,
    first_name,
    middle_name,
    position,
    department,
    employment_status,
    date_hired,
    active
) VALUES (
    'KPFH-C080',
    'Abaredes',
    'Malaya',
    'A.',
    'Nursing Attendant I',
    'Nursing',
    'Casual',
    '2010-02-08',
    1
);
