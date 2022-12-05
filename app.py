import pandas as pd
import numpy as np
from flask import Flask, request, jsonify
import pickle
import json
from sklearn.preprocessing import OrdinalEncoder
from numpyencoder import NumpyEncoder

app = Flask(__name__)
model = pickle.load(open('newmodel.pkl','rb'))

@app.route('/dwell', methods=['GET'])
def home():
    df = pd.DataFrame({
        'age': [45, 55, 39, 32], 
        'gender': ['Male', 'Female', 'Female', 'Male'],
        'health': ['Healthy', 'Healthy', 'Minor Health Condition', 'Healthy'],
        'type': ['Student', 'Student', 'Student', 'Student'],
        'exp': ['No', 'No', 'No', 'No'],
        'spot': ['Culture', 'Culture', 'Culture', 'Culture'],
        'weather': ['Sunny', 'Sunny', 'Sunny', 'Sunny'],
        'temperature': [-2, -2, -2, -2],
        'humidity': [55, 55, 55, 55]
    })
    ord_enc2 = OrdinalEncoder()
    df["gender"] = ord_enc2.fit_transform(df[["gender"]])
    df["health"] = ord_enc2.fit_transform(df[["health"]])
    df["type"] = ord_enc2.fit_transform(df[["type"]])
    df["exp"] = ord_enc2.fit_transform(df[["exp"]])
    df["spot"] = ord_enc2.fit_transform(df[["spot"]])
    df["weather"] = ord_enc2.fit_transform(df[["weather"]])
    prediction = model.predict(df)
    average = 5 * round(sum(prediction)/len(prediction)/5)
    data = {
        "status": True,
        "dwellingTime": str(int(average))
        }
    response = app.response_class(response=json.dumps(data), status=200, mimetype='application/json')
    return response
    # return "test"

@app.route('/dwellpost', methods=['POST'])
def post():
    content = request.json
    df = pd.DataFrame()
    for x in content['tourist']:
        df2 = {
                    'age': x['age'],
                    'gender': x['gender'],
                    'health':x['health'],
                    'type': x['type'],
                    'exp': x['exp'],
                    'spot': x['spot'],
                    'weather': x['weather'],
                    'temperature': x['temperature'],
                    'humidity': x['humidity']
                }
        df = df.append(df2, ignore_index=True)
    ord_enc = OrdinalEncoder()
    df["gender"] = ord_enc.fit_transform(df[["gender"]])
    df["health"] = ord_enc.fit_transform(df[["health"]])
    df["type"] = ord_enc.fit_transform(df[["type"]])
    df["exp"] = ord_enc.fit_transform(df[["exp"]])
    df["spot"] = ord_enc.fit_transform(df[["spot"]])
    df["weather"] = ord_enc.fit_transform(df[["weather"]])
    prediction = model.predict(df)
    average = 5 * round(sum(prediction)/len(prediction)/5)
    return str(int(average))
    
if __name__ == '__main__':
    app.run(debug=True)