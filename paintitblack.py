import os
from PIL import Image

def replace_black_pixels(image_path, output_path, new_color=(255, 0, 0)):
    # Ouvre l'image
    img = Image.open(image_path).convert("RGBA")
    pixels = img.load()
    
    # Remplace les pixels noirs
    for y in range(img.height):
        for x in range(img.width):
            if pixels[x, y][:3] == (0, 0, 0):  # Vérifie si le pixel est noir
                pixels[x, y] = new_color + (pixels[x, y][3],)  # Remplace la couleur
    
    # Sauvegarde l'image modifiée
    img.save(output_path)

def process_images(input_folder, output_folder, new_color=(255, 0, 0)):
    if not os.path.exists(output_folder):
        os.makedirs(output_folder)
    
    for filename in os.listdir(input_folder):
        if filename.lower().endswith(('png', 'jpg', 'jpeg')):
            input_path = os.path.join(input_folder, filename)
            output_path = os.path.join(output_folder, filename)
            replace_black_pixels(input_path, output_path, new_color)
            print(f"Image traitée : {filename}")

# Exemple d'utilisation
input_folder = "/Users/electrocard/Documents/GitHub/hexhal.com/resource/icon"  # Dossier contenant les images originales
output_folder = "/Users/electrocard/Documents/GitHub/hexhal.com/lol"  # Dossier pour sauvegarder les images modifiées
new_color = (255, 76, 25)  # Rouge en remplacement du noir

process_images(input_folder, output_folder, new_color)
