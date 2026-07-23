SELECT
    ccd.id,

    e.employee_code,
    e.full_name,

    tt.transportation_name,

    v.vehicle_name,
    v.plate_number,

    d.driver_name,

    ccd.pickup_date,
    ccd.pickup_time,
    ccd.pickup_location,
    ccd.destination,

    ccd.status,
    ccd.remarks,
    ccd.created_at

FROM company_car_departures ccd

INNER JOIN room_assignments ra
    ON ccd.assignment_id = ra.id

INNER JOIN employees e
    ON ra.employee_id = e.id

LEFT JOIN transportation_types tt
    ON ccd.transportation_type_id = tt.id

LEFT JOIN vehicles v
    ON ccd.vehicle_id = v.id

LEFT JOIN drivers d
    ON ccd.driver_id = d.id

ORDER BY
    ccd.pickup_date DESC,
    ccd.pickup_time DESC;