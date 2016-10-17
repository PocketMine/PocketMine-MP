//includes:
#include <iostream>
#include <fstream>

using namespace std;
//main function:
int main(){
//creates a string thats is used to save the command on
string command;
//Opens a file to write on it and remove everything on it
fstream console("console.IN", ios::out || ios::trunc)
//checks if it is opend
if (console.is_open()){
do{
//write / on the screen
cout << "/";
//lets the use type the command
cin >> command;
cout << "\n";
//print the command to the console.IN file
console << "/" << command;
if(command == "stop")break;
//close the file
console.close();
//open it
console.open("console.IN", ios::out || ios::trunc)
//check if everything works
}while((running = true) || (myfile.is_open()))
}
//self explaining =)
else cout << "Unable to open file";
cin.get()
cin.get()
}
