# ChatGpt Google Calendar meeting

## 1. Introduction
This repository contains a Symfony project with a chatbot that uses the ChatGPT API and the Google API to provide a seamless experience for users. The chatbot is designed to handle a variety of tasks, including answering questions, providing information, and scheduling meetings.
[Visually demo (No OpenAI)](https://moh-snoussi.github.io/chatgpt_google_callendar/).

[![Youtube video](https://img.youtube.com/vi/TlAyqe__qOE/0.jpg)](https://youtu.be/TlAyqe__qOE)


### how it works
We use regex to scan each OpenAI response to check if it contains a meeting confirmation. If a meeting confirmation is found, the server calls the Google Calendar API to create a meeting.

## 2. Requirements
- PHP > 8
- Composer
- OpenAI API key
- Google Calendar ID
- Google Oath2 Client ID
- Google Oath2 API secret

## 2. Installation

```bash 
git clone git@github.com:Moh-Snoussi/chatgpt_google_callendar.git
cp .env .env.local
composer install
# Adjust the .env.local file with your own values
# Download the Google Client Secret file and save it as client_secret.json in the root of the project
# Run the server
symfony server:start -d
# Go to the chat page: https://localhost:8000/connect/google
# add the google redirect url to the google console
# Go back to the login page: https://localhost:8000/connect/google and login with google
# Go to the chat page: https://localhost:8000/chat and start chatting with the bot.
```

## 3. Configuration
The .env.local variables need to be set with your own values.

### OpenAi:
OpenAI key is used to answer the chat messages, we use the chat-gpt-3.5-turbo model
System message is found in the root of the project in the file: system_message.txt
the system message is used to give context to the our model, it can be adjusted by setting the variables GPT_SYS_MESSAGE_* in the .env.local file.

OpenAI API Key can be taken from the OpenAI Dashboard:
OpenAI Dashboard -> API Keys -> Create API Key
Copy the key and paste it in the .env.local OPENAI_API_KEY variable.

### Google Client Secret
Google client secret is used to allow the user to create a calendar event and add invitees.
Google Client Secret file can be taken from the Google API Console, 
- Create a project https://console.cloud.google.com/projectcreate.
- Click on the new created project
- Create Oauth Credentials: API & Services -> Credentials -> Create Credentials -> OAuth Client ID
- Click on the new created credential -> on the right side -> client secret -> download the JSON file.
- Save the JSON file as client_secret.json in the root of the project.

### Google Calendar ID
Google Calendar ID can be taken from the Google Calendar Settings:
Google Calendar -> click on the calendar -> Settings & Sharing -> Integrate calendar -> Calendar ID

### ALLOW_CALENDAR_OWNER_LOGIN=1
Allows the page /connect/google to be used to login with google.
this is required only once, to allow the user to login and set the calendar owner.
Once you are logged in, you can set the variable to 0.
Make sure that the user that login with google is the owner of the calendar or has the permission to create events in the calendar:
Google Calendar -> click on the calendar -> Settings & Sharing -> Share with specific people -> Add people -> search for the user -> select the user -> set the permission to "Make changes to events" -> save.

## 4. Usage
Once the project is installed and configured, you can start the server by running:
```bash
symfony server:start
```
now you can open the project in your browser by going to (you may need to adjust the port number):
https://127.0.0.1:8000/connect/google/
copy the last link that appear on that page and add it to Google Console -> API & Services -> Credentials -> OAuth 2.0 Client IDs -> your client ID -> Authorized redirect URIs, and save it.
Now you can go back to the page (https://127.0.0.1:8000/connect/google/) and click "Login with google" the link, and login with your google account.

Go to the chat page: https://127.0.0.1:8000/chat and start chatting with the bot.

## 5. Additional information

I made a conscious effort to minimize the system dependencies of this project, and have therefore decided not to use a database. This makes it easy to deploy, without worrying about setting up and maintaining a database.

Chat history is stored in the var/log/*.log


