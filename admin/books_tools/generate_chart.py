import mysql.connector
import matplotlib.pyplot as plt
import os

try:
    # Get the directory where this script is located
    script_dir = os.path.dirname(os.path.abspath(__file__))

    # Path to the charts folder inside the script folder
    output_dir = os.path.join(script_dir, "charts")

    # Create the charts folder if it doesn't exist
    os.makedirs(output_dir, exist_ok=True)

    # Full path where the chart image will be saved
    output_path = os.path.join(output_dir, "monthly_chart.png")

    # print("Saving chart to:", output_path)

    # Connect to MySQL
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",  # update if you have a password
        database="library_test_db"
    )
    cursor = conn.cursor()

    cursor.execute("""
        SELECT DATE_FORMAT(date_added, '%Y-%m') as month, COUNT(*) 
        FROM books 
        GROUP BY month 
        ORDER BY month ASC
    """)
    data = cursor.fetchall()
    conn.close()

    if not data:
        print("No book data found.")
        exit()

    months = [row[0] for row in data]
    counts = [row[1] for row in data]

    plt.figure(figsize=(10, 5))
    plt.bar(months, counts, color='skyblue')
    plt.title("Monthly Book Additions")
    plt.xlabel("Month")
    plt.ylabel("Books Added")
    plt.xticks(rotation=45)
    plt.tight_layout()

    # Save the figure
    plt.savefig(output_path)



except Exception as e:
    print("Error:", e)
