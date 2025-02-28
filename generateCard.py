import datetime
import sys
import os
from PIL import Image, ImageDraw, ImageFont

def generate_card(full_name, beneficiary_name, relation_name, card_number):
    try:
        # Load the card templates
        front_template_path = "templates/front_template.png"
        back_template_path = "templates/back_template.png"
        output_dir = "outputs/img"
        os.makedirs(output_dir, exist_ok=True)

        if not os.path.exists(front_template_path) or not os.path.exists(back_template_path):
            raise FileNotFoundError("One or both card templates not found.")
        
        positions = {
            "cardNumber"        : (350, 455),
            "date"              : (850, 455),
            "fullName"          : (350, 555),
            "beneficiaryName"   : (410, 340),
        }

        font_path = "calibri.ttf"
        font_color = "#FFA200"
        anchor = "mm"

        try:
            font = ImageFont.truetype(font_path, 40)
        except IOError:
            print(f"Error: Font file '{font_path}' not found.")
            sys.exit(1)

        # Process front template
        front_image = Image.open(front_template_path)
        front_draw = ImageDraw.Draw(front_image)
        
        front_draw.text(positions["fullName"], f"{full_name}", fill=font_color, anchor=anchor, font=font)
        front_draw.text(positions["cardNumber"], f"{card_number}", fill=font_color, anchor=anchor, font=font)
        front_draw.text(positions["date"], datetime.datetime.now().strftime("%m%d%Y") , fill=font_color, anchor=anchor, font=font)
        
        # Save the front card
        front_output_path = os.path.join(output_dir, full_name.split().pop().upper() + "_" + card_number + "_front.png")
        front_image.save(front_output_path)
        
        # Process back template
        back_image = Image.open(back_template_path)
        back_draw = ImageDraw.Draw(back_image)
        

        back_draw.text(positions["beneficiaryName"], f"{beneficiary_name}/{relation_name}", fill=font_color, anchor=anchor, font=font)
        
        # Save the back card
        back_output_path = os.path.join(output_dir, full_name.split().pop().upper() + "_" + card_number + "_back.png")
        back_image.save(back_output_path)
        
        print(front_output_path)
        print(back_output_path)  # Return both file paths to PHP
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    if len(sys.argv) < 5:
        print("Error: Missing required arguments.")
        sys.exit(1)
    
    full_name = sys.argv[1]
    beneficiary_name = sys.argv[2]
    relation_name = sys.argv[3]
    card_number = sys.argv[4]
    
    generate_card(full_name, beneficiary_name, relation_name, card_number)