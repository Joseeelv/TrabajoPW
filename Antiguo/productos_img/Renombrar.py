import os

# Lista de nuevos nombres de archivo en orden
new_names = [
    "döner_pollo.png", "döner_ternera.png", "döner_cordero.png", "döner_vegetariano.png",
    "durum_pollo.png", "durum_ternera.png", "durum_cordero.png", "durum_vegetariano.png",
    "lahmacun_pollo.png", "lahmacun_ternera.png", "lahmacun_cordero.png", "lahmacun_vegetariano.png",
    "patatas_fritas.png", "patatas_kebab.png", "falafel.png",
    "refresco_pequeño.png", "refresco_mediano.png", "refresco_grande.png", "cerveza.png", "agua.png",
    "baklava.png", "helado.png"
]

# Renombrar los archivos
for i, new_name in enumerate(new_names, start=1):
    old_name = f"{i}.png"
    if os.path.exists(old_name):
        os.rename(old_name, new_name)
        print(f'Renombrado: {old_name} → {new_name}')
    else:
        print(f'No encontrado: {old_name}')

print("Proceso completado.")
