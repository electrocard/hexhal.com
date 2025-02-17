from flask import Flask, request, jsonify
import json
import requests

app = Flask(__name__)

@app.route('/traitement', methods=['POST'])
def traitement():
    # Récupérer les données soumises par le formulaire
    question = request.form.get('question')
    fichier_json = request.form.get('fichier_json')

    # Ajouter le nouveau message de l'utilisateur au fichier JSON
    with open(fichier_json, 'r') as f:
        conversation = json.load(f)
    nouveau_message_utilisateur = {"role": "user", "content": question}
    conversation["messages"].append(nouveau_message_utilisateur)
    with open(fichier_json, 'w') as f:
        json.dump(conversation, f, indent=2)

    # Faire la requête HTTP vers l'API
    url = 'http://(l\'ip du society_info.json de l\'utilisateur)/api/chat'
    headers = {'Content-Type': 'application/json'}
    response = requests.post(url, data=json.dumps(conversation), headers=headers)

    # Vérifier la réponse de la requête
    if response.status_code == 200:
        # Extraire la réponse JSON de la requête
        reponse_json = response.json()
        contenu_message_assistant = reponse_json.get("message", "")

        # Ajouter la réponse de l'assistant au fichier JSON
        conversation["messages"].append({"role": "assistant", "content": contenu_message_assistant})
        with open(fichier_json, 'w') as f:
            json.dump(conversation, f, indent=2)
        
        return jsonify({"success": True, "message": "Réponse ajoutée au fichier JSON avec succès !"})
    else:
        return jsonify({"success": False, "error": f"Erreur lors de l'envoi de la requête : {response.status_code}"})

if __name__ == '__main__':
    app.run(debug=True)
