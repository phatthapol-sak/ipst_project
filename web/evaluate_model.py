from flask import Flask, request, jsonify
import tensorflow as tf
import numpy as np
import pandas as pd
from pythainlp import word_tokenize
from pythainlp.corpus.common import thai_stopwords
from pythainlp import word_vector
import joblib

app = Flask(__name__)

# Load your pre-trained models (update this path to your model files)
lstm_model = tf.keras.models.load_model(r'C:\xampp\htdocs\project\LSTMXG.h5')
stacking_model = joblib.load(r'C:\xampp\htdocs\project\stacking_model.pkl')

# Initialize Thai2Fit model and tokenizer
word_model = word_vector.WordVector(model_name="thai2fit_wv").get_model()
thai2dict = {word: word_model[word] for word in word_model.index_to_key}
thai2vec = pd.DataFrame.from_dict(thai2dict, orient='index')
thai2dict_list = list(thai2dict)

# Define stopwords
stop_words = set(thai_stopwords())

def preprocess(text, thai2dict, stop_words):
    tokens = word_tokenize(text, engine='newmm', keep_whitespace=False)
    tokens = [token for token in tokens if len(token) > 1 and token not in stop_words]
    indices = [thai2dict_list.index(token) for token in tokens if token in thai2dict_list]
    return indices

def pad_sequences(sequences, maxlen):
    padded_sequences = np.zeros((len(sequences), maxlen))
    for i, seq in enumerate(sequences):
        if len(seq) > maxlen:
            padded_sequences[i] = np.array(seq[:maxlen])
        else:
            padded_sequences[i, :len(seq)] = np.array(seq)
    return padded_sequences

def evaluate(messages):
    # Preprocess messages
    processed_messages = [preprocess(msg, thai2dict, stop_words) for msg in messages]
    
    # Define maxlen based on training (adjust as needed)
    maxlen = 100
    processed_messages = pad_sequences(processed_messages, maxlen)
    
    # Extract features using LSTM model
    lstm_features = lstm_model.predict(processed_messages)
    
    # Evaluate messages using Stacking model
    results = stacking_model.predict(lstm_features)
    
    return results.tolist()

@app.route('/evaluate', methods=['POST'])
def evaluate_endpoint():
    data = request.json
    messages = data['messages']
    results = evaluate(messages)
    return jsonify(results)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
