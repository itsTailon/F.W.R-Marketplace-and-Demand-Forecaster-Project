/* getCharCount(str, char)
Description: Find the number of a given character in a string
Parameters:
- str: The string to search
- char: The character to count
Returns the number of char in str
*/
const getCharCount = (str, char) => {
    let count = 0;
    if (str === "") { return 0; } // If the string is empty just return 0
    for (const c of str) { // Iterate through each character in str
        if (c === char) { count++; } // If the character is equal to char, increment count
    }
    return count;
}

/* getCharSetCount(str, chars) 
Description: Find the number of occurrences of any 
characters from a set of characters in a string
- str: The string to search
- chars: The set of characters
Returns the number of times any character in chars appears
in str
*/
const getCharSetCount = (str, chars) => {
    let count = 0;
    if (str === "") { return 0; } // If the string is empty just return 0
    for (const c of str) { // Iterate through each character in str
        if (chars.includes(c)) { count++; } // If the character is in the set of chars, increment count
    }
    return count;
}

/* validateEmail(email)
Description: Determine if an email is in valid format
Parameters:
- email: The email to validate
Returns true or false depending on if the email is valid
*/
const validateEmail = email => {
    if (getCharCount(email, '@') != 1) { return false; } // If there is not exactly one @, email is invalid
    if (getCharCount(email, '.') == 0) { return false; } // If there is not at least one ., email is invalid
    let emailSplit = email.split('@'); // Split the email into two at the @
    if (emailSplit[0].length == 0 || emailSplit[1].length == 0) { return false; } // If either side is empty, email is invalid
    return true; // Otherwise, return true as the email has passed all checks
}

/* validatePassword(password) 
Description: Determine if a password meets the requirements
Parameters:
- password: Password to be checked
Returns true or false depending on if the password is allowed
*/
const validatePassword = password => {
    if (password.length < 8) { return "Password must be at least 8 characters" }
    else if (getCharSetCount(password, "1234567890") == 0) { return "Password must contain at least one number" }
    else if (getCharSetCount(password, "abcdefghijklmnopqrstuvwxyz") == 0 || getCharSetCount(password, "ABCDEFGHIJKLMNOPQRSTUVWXYZ") == 0) { return "Password must include both upper and lowercase letters" }
    else if (getCharSetCount(password, "!\"£$%^&*();:'@#~,.<>/?{}[]_-+=") == 0) { return "Password must contain at least one special character" }
    return "PASS"
}
