import sys
import pandas as pd
import matplotlib.pyplot as plt

# Get the input CSV and output image path from command line arguments
input_csv = sys.argv[1]
output_image = sys.argv[2]

# Read the CSV file
data = pd.read_csv(input_csv)

# Generate a bar plot
plt.figure(figsize=(10, 6))
plt.bar(data['IP Address'], data['Packet Count'], color='skyblue')
plt.title('Packet Count per IP Address')
plt.xlabel('IP Address')
plt.ylabel('Packet Count')
plt.xticks(rotation=45)
plt.tight_layout()

# Save the figure
plt.savefig(output_image)
plt.close()
