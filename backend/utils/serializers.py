from datetime import date, datetime
from decimal import Decimal


def serialize_value(value):
    if isinstance(value, (datetime, date)):
        return value.isoformat()
    if isinstance(value, Decimal):
        return float(value)
    return value


def serialize_row(row: dict) -> dict:
    return {key: serialize_value(val) for key, val in row.items()}


def serialize_rows(rows: list) -> list:
    return [serialize_row(row) for row in rows]
