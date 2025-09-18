#!/usr/bin/env python3
"""Convert Excel file to CSV for Firefly III account import (optional utility)"""
import os
import sys

import pandas as pd


def convert_excel_to_csv(excel_file, csv_file=None):
    """Convert Excel file to CSV format"""
    if csv_file is None:
        csv_file = excel_file.replace(".xlsx", ".csv").replace(".xls", ".csv")

    try:
        # Read Excel file
        df = pd.read_excel(excel_file)

        # Clean up column names
        df.columns = df.columns.str.strip().str.lower()

        # Save as CSV
        df.to_csv(csv_file, index=False)
        print(f"Successfully converted {excel_file} to {csv_file}")
        return csv_file

    except Exception as e:
        print(f"Error converting file: {e}")
        return None


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python convert_excel_to_csv.py <excel_file> [csv_file]")
        sys.exit(1)

    excel_file = sys.argv[1]
    csv_file = sys.argv[2] if len(sys.argv) > 2 else None

    if not os.path.exists(excel_file):
        print(f"File {excel_file} does not exist")
        sys.exit(1)

    convert_excel_to_csv(excel_file, csv_file)
