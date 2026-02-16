var streakStart;
var streakEnd;

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
const monthLengths = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

// Get day Monday to Sunday
// 0 is Monday, 6 is Sunday
const getDayMtS = (d) => {
    const day = d.getDay();
    return (day > 0) ? day - 1 : 6;
}


/* getWeekStart
Return a date object for the start of the week of a given day
Parameters:
- d: Date we want the start of week for
Returns: A Date object for the day at the start of the week
*/
const getWeekStart = (d) => {
    const day = getDayMtS(d);
    const nd = d;
    nd.setDate(nd.getDate() - day);
    return nd;
}

/* getWeekEnd
Return a date object for the final day of the week of a given day
Parameters:
- d: Date we want the end of week for
Returns: A Date object for the final day of the week
*/
const getWeekEnd = (d) => {
    const day = getDayMtS(d);
    const nd = d;
    nd.setDate(nd.getDate() + (6-day));
    return nd;
}

/* getMonthLength
Get the number of days in a month in a certain year. Accounts
for leap years.
Parameters:
- m: Month from 0 to 11
- y: Year
*/
const getMonthLength = (m, y) => {
    // If month is February and year is divisible by 4 (a leap year)
    // return 29, otherwise look in monthLengths array
    return (m == 1 && y % 4 == 0) ? 29 : monthLengths[m];
}

/* updateCalendar
Update the calendar widget on the page if the user clicks on the previous
or next buttons, and on the page loading.
*/

function updateCalendar() {
    $(".calendar__menu__header").text(months[selMonth] + " " + selYear.toString()); // Update the calendar title to say the new month and year

    const first = new Date(selYear, selMonth, 1); // Create a Date object for the first day of the month
    const firstDay = getDayMtS(first); // Get the weekday the first day falls on

    // Get information about the previous month so we can show the other days in the week of the starting day
    const lastMonth = (selMonth > 0) ? selMonth - 1 : 11; // If month is January, last month was December, otherwise last month is subtract 1 from selected
    const lastMonthYear = (lastMonth == 11) ? selYear - 1 : selYear; // If last month is December, then that means year was 1 less, otherwise year is the same
    const lastMonthLen = getMonthLength(lastMonth, lastMonthYear); // Get length of last month in its year

    // table array will be populated with rows of the calendar with Date objects of this month and overlapping weeks of previous/next month
    var table = [];

    var count = 0; // We will use this to determine when to move onto a new row

    var row = [];

    // Get days from the previous month that are in the same week as the first day
    for (let i = firstDay-1; i >= 0; i--) {
        row.push(new Date(lastMonthYear, lastMonth, lastMonthLen - i)); // Push Date object

        if (++count % 7 == 0) { // If seven days have been pushed to the row array
            table.push(row); // Push row to table
            row = []; // Reset row
        }
    }

    const now = new Date(); // Get Date object for the current time; we'll need this to highlight the current day if it is visible

    const monthLen = getMonthLength(selMonth, selYear); // Get month length of the selected month in the selected year

    // Push all the days in the selected month to the table array
    for (let i = 1; i <= monthLen; i++) {
        row.push(new Date(selYear, selMonth, i));
        
        if (++count % 7 == 0) {
            table.push(row);
            row = [];
        }
    }

    // Get days in next month in the same week as the last day
    const last = new Date(selYear, selMonth, monthLen); // monthLen is the same as the last day of the month
    const lastDay = getDayMtS(last); // Get the weekday the last day falls on

    const nextMonth = (selMonth == 11) ? 0: selMonth + 1; // If selected month is December, next month is January, otherwise next month is 1 ahead
    const nextMonthYear = (nextMonth == 0) ? selYear + 1 : selYear; // If next month is January, then next month's year is 1 ahead, otherwise it is the same

    // Push the days in the next month that overlap with the week of the final day of the selected month
    for (let i = 1; i < 7 - lastDay; i++) {
        row.push(new Date(nextMonthYear, nextMonth, i));

        if (++count % 7 == 0) {
            table.push(row);
            row = [];
        }
    }

    if (row.length > 0) {
        table.push(row);
    }

    // Create HTML content to be added to calendar table
    // Initials for days of the week as the headers
    var html = `
        <tr>
            <th>M</th>
            <th>T</th>
            <th>W</th>
            <th>T</th>
            <th>F</th>
            <th>S</th>
            <th>S</th>
        </tr>
    `;

    const properStreakStart = (streakStart == null) ? null : getWeekStart(streakStart);
    const properStreakEnd = (streakEnd == null) ? null : getWeekEnd(streakEnd);

    // Reset count back to 0
    count = 0;

    // Iterate through each row in table
    table.forEach(row => {
        var rowHtml = "<tr>"; // Start HTML table row
        row.forEach(date => {
            var cssClasses = [];
            // If month of date is not the selected month, the day will be shown in grey
            if (date.getMonth() != selMonth) {
                cssClasses.push("grey");
            }

            var dayStr = date.getDate().toString(); // Convert date to string
            if (dayStr.length == 1) { dayStr = "0" + dayStr; } // If day is single digit, prepend a 0

            // If day is today, display a black border around it on the calendar
            if (date.getDate() == now.getDate() && date.getMonth() == now.getMonth() && date.getFullYear() == now.getFullYear()) {
                dayStr = `<div class="today">${dayStr}</div>`;
            }

            /* Determine what streak classes will be applied
            There are three options
            - streak: For any date within the period of the streak
            - start: For any Monday within the period of the streak, or the starting day of the streak
            - end: For any Sunday within the period of the streak, or the ending day of the streak
            */
            var streakClasses = [];
            if (date >= properStreakStart && date <= properStreakEnd) { // If date is in the streak range
                streakClasses.push("streak"); // Assign it the streak class
                // If the date is the starting day of the streak or a Monday during the streak
                if ((date.getDate() == properStreakStart.getDate() && date.getMonth() == properStreakStart.getMonth() && date.getFullYear() && properStreakStart.getFullYear())
                || count % 7 == 0) {
                    streakClasses.push("start"); // Assign the start class, this will give it round edges on the left
                } else if ((date.getDate() == properStreakEnd.getDate() && date.getMonth() == properStreakEnd.getMonth() && date.getFullYear() && properStreakEnd.getFullYear())
                || count % 7 == 6) {
                    // If the date is the ending day of the streak or a Sunday during the streak
                    streakClasses.push("end"); // Assign the end class, this will give it round edges on the right
                }

            }

            // Enclose day string in a div with the given streak classes if there are any
            if (streakClasses.length > 0) {
                dayStr = `<div class="${streakClasses.join(" ")}">${dayStr}</div>`;
            }
            
            // Embed the day string in a table column with any given classes
            var colHtml = `<td${cssClasses.length > 0 ? ` class="${cssClasses.join(" ")}"` : ""}>${dayStr}</td>`;
            rowHtml += colHtml; // Add column HTML to row
            count++; // Increment count so we can track what day of the week we are on
        })
        rowHtml += "</tr>"; // End the row
        html += rowHtml; // Add row to table HTML
    });

    $("#calendar").html(html); // Set HTML of calendar table
    // Set the width and height of the table columns and rows
    $("table.calendar__table td, table.calendar__table tr").css('width', $(".calendar__table").width() / 7);
    $("table.calendar__table td, table.calendar__table tr").css('height', $(".calendar__table").width() / 7);

}

// Set the selected month and year to the current when loading the page
const d = new Date(); // Get current date

// Get current month and year
var selMonth = d.getMonth();
var selYear = d.getFullYear();

// If previous button is clicked, go back 1 month
$('#prev-btn').click(() => {
    if (--selMonth == -1) { // If going back a month would go out of the 0-11 month range
        selMonth = 11; // Set month to 11 (December)
        selYear--; // Decrement year
    }

    updateCalendar(); // Update calendar to show the new month and year
});

// If next button is clicked, go forward 1 month
$('#next-btn').click(() => {
    if (++selMonth == 12) { // If going forward a month would go out of the 0-11 month range
        selMonth = 0; // Set month to 0 (January)
        selYear++; // Increment year
    }

    updateCalendar();
});

$.ajax({
    url: "/backend/API/Model/streak.php",
    type: 'GET',
    success: dates => {
        if (dates["startDate"] == null || dates["endDate"] == null || dates["currentWeekStart"] == null) {
            streakStart = null;
            streakEnd = null;
        } else {
            let sqlStartDate = dates["startDate"]["date"];
            let sqlStartSplit = sqlStartDate.replace(' ', '-').split("-");
            streakStart = new Date(sqlStartSplit[0], sqlStartSplit[1]-1, sqlStartSplit[2]);

            let sqlEndDate = dates["currentWeekStart"]["date"];
            let sqlEndSplit = sqlEndDate.replace(' ', '-').split("-");
            streakEnd = new Date(sqlEndSplit[0], sqlEndSplit[1]-1, sqlEndSplit[2]);

            let weeks = 0;

            for (let d = new Date(streakStart.getFullYear(), streakStart.getMonth(), streakStart.getDate()); d <= streakEnd; d.setDate(d.getDate() + 7), weeks++);

            $("#weeks").text(weeks.toString() + " " + (weeks == 1 ? "week" : "weeks"));
        }

        updateCalendar();
    }
});
