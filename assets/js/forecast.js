/* Graph class
Used for displaying statistics.
Current types: Bar chart
TBA: Line graphs
*/

class Graph {
    /* Constructor
    Parameters:
    - type: The type of graph
    - width: Desired size of canvas
    - height: Desired size of canvas
    - rows: How many horizontal, equally spaced faded lines there will be
    - cols: How many vertical, equally spaced faded lines there will be
    - padding: Space between the corners of the canvas and beginning/end of graph
    */
    constructor(type, width, height, rows, cols, padding) {
        this.type = type;
        this.width = width;
        this.height = height;
        this.rows = rows;
        this.cols = cols;
        this.padding = padding;

        // Lists of labels for each axis, one for each column (X) or row (Y)
        this.xAxisLabels = [];
        this.yAxisLabels = [];

        // Calculate the size of each column and row
        this.colWidth = (width - 2*padding) / cols;
        this.rowHeight = (height - 2*padding) / rows;

        // Get canvas, set dimensions, and get context
        this.canvas = document.getElementById("graph");
        this.canvas.width = width;
        this.canvas.height = height;
        this.ctx = this.canvas.getContext("2d");

        // Store colour keys for bar charts
        this.keys = {};
        this.keySpace = 0; // Extra space to be added to bottom to accommodate keys

        this.plot = new Map();
    }

    /* generateAxisLabels
    Generate equally-spaced labels for an axis in a given range
    Parameters:
    - start: The start of the range
    - end: The end of the range
    - divisor: The number of labels to generate
    Return: The generated labels
    */
    generateAxisLabels(start, end, divisor) {
        let axisLabels = []
        for (let i = start; i <= end; i += (end-start)/divisor) {
            axisLabels.push(Math.round(i));
        }
        return axisLabels;
    }

    /* generateXAxisLabels
    Generate equally-spaced labels within a certain range for the X axis
    */
    generateXAxisLabels(start, end) {
        // Set X axis labels to the result of generateAxisLabels with cols as the number of labels
        this.setXAxisLabels(this.generateAxisLabels(start, end, this.cols));
    }

    /* generateYAxisLabels
    Generate equally-spaced labels within a certain range for the Y axis
    */
    generateYAxisLabels(start, end) {
        // Set Y axis labels to the result of generateAxisLabels with rows as the number of labels
        this.setYAxisLabels(this.generateAxisLabels(start, end, this.rows));
    }

    /* setXAxisLabels
    Manually set a list of labels for the X axis
    Parameters:
    - labels: List of labels
    */
    setXAxisLabels(labels) {
        this.xAxisLabels = labels;
        this.xAxisLabels.forEach(label => {
            if (!this.plot.has(label)) {
                this.plot.set(label, new Map());
            }
        });
    }

    /* setYAxisLabels
    Manually set a list of labels for the Y axis
    Parameters:
    - labels: List of labels
    */
    setYAxisLabels(labels) {
        this.yAxisLabels = labels;
    }

    /* draw
    Draw the graph
    */
    draw() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height) 
        // Draw axes
        this.ctx.fillStyle = "rgb(0 0 0)";
        this.ctx.fillRect(this.padding, this.padding, 2, this.height - 2*this.padding - this.keySpace);
        this.ctx.fillRect(this.padding, this.height - this.padding - this.keySpace, this.width - 2*this.padding, 2);

        // Draw rows and columns
        this.ctx.fillStyle = "rgb(130 130 130)";

        for (let c = this.padding - this.colWidth; c < this.width - this.padding - 1; c += this.colWidth) {
            this.ctx.fillRect(c, this.padding, 0.5, this.height - 2*this.padding - this.keySpace);
        }
        for (let r = this.padding + this.rowHeight; r < this.height - this.padding - this.keySpace; r += this.rowHeight) {
            this.ctx.fillRect(this.padding, r, this.width - 2*this.padding, 0.5);
        }

        // Draw labels
        this.ctx.textAlign = "center";
        this.ctx.textBaseline = "middle";
        this.ctx.fillStyle = "rgb(0 0 0)";
        this.ctx.font = "12px sans-serif";

        let x = (this.type === "bar") ? this.padding + 0.5*this.colWidth : this.padding;
        let y = this.height - 0.5*this.padding - this.keySpace;

        this.xAxisLabels.forEach(label => {
            this.ctx.fillText(label, x, y);
            x += this.colWidth;
        });

        x = 0.5*this.padding;
        y = this.height - this.padding - this.keySpace;

        this.yAxisLabels.forEach(label => {
            this.ctx.fillText(label, x, y);
            y -= this.rowHeight;
        });

        let yAxisLength = this.height - 2*this.padding - this.keySpace;
        let yAxisScale = yAxisLength / this.yAxisLabels[this.yAxisLabels.length-1];

        // Draw colour key (if bar chart)
        if (this.type === "bar") {
            y = this.height - this.keySpace;
            
            // Display the keys below the graph
            Object.keys(this.keys).forEach(key => {
                let colour = this.keys[key];
                this.ctx.fillStyle = colour;
                let x = this.padding;
                this.ctx.fillRect(x - 7.5, y - 7.5, 15, 15);
                x += 15;
                this.ctx.fillStyle = "rgb(0 0 0)";
                this.ctx.textAlign = "left";
                this.ctx.fillText(key, x, y);
                y += 20;
            });

            this.ctx.textAlign = "center";
            this.ctx.textBaseline = "top";
            let x = this.padding + 0.5*this.colWidth;

            // Draw the bar chart
            this.xAxisLabels.forEach(label => {
                if (this.plot.has(label)) { // If X value has a corresponding bar
                    let col = this.plot.get(label);

                    let y = this.height - this.padding - this.keySpace;

                    // Draw proportion of bar for each key
                    Object.keys(this.keys).forEach(key => {
                        if (col.has(key)) {
                            let val = col.get(key);
                            if (val > 0) {
                                let colour = this.keys[key]; // Get colour of bar
                                this.ctx.fillStyle = colour;
                                // Draw proportion of bar
                                this.ctx.fillRect(x - 0.3*this.colWidth, y - (yAxisScale*val), 0.6*this.colWidth, (yAxisScale*val));
                                // Draw text on top of bar for its value
                                this.ctx.fillStyle = "#000000";
                                this.ctx.fillText(`${val}`, x, y - (yAxisScale*val) + 6);
                                y -= (yAxisScale*val); // Move Y up the height of the bar
                            }
                            
                        }
                    });
                }
                x += this.colWidth; // Move to the next column
                
            })
        }
    }

    // Add colour key with text to a bar chart
    addKey(colour, text) {
        if (this.type === "bar") { // Check if type is bar
            this.keys[text] = colour; // If so, set the colour to correspond to text
            // Grow the graph from the bottom to make room for keys
            this.height += 20;
            // Increase space at bottom of canvas to accommodate key
            this.keySpace += 20;
            this.canvas.height = this.height;
            this.ctx = this.canvas.getContext("2d");
        }
    }

    // Plot on the bar chart
    plotBar(xVal, yVal, key) {
        let column = new Map();
        if (this.plot.has(xVal)) {
            column = this.plot.get(xVal);
        }
        column.set(key, yVal);
        this.plot.set(xVal, column);
    }
};

// Get the maximum value in an array
const getMaxValue = (arr) => {
    let max = 0; // Start with a maximum of 0
    arr.forEach(item => { // Iterate through each element
        if (item > max) { // If element is more than maximum
            max = item; // Set maximum to element value
        }
    });
    return max;
}

const graph = new Graph("bar", 1000, 500, 8, 7, 50); // Create graph
// X axis (bottom) will be days of the week
graph.setXAxisLabels(["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]);
graph.generateYAxisLabels(0, 200);

// Add keys for show and no show
graph.addKey("#3393dc", "Show");
graph.addKey("#f69d0d", "No Show");

// When start hour number field is changed, make sure it is two digits
$("#start-hr").on("input", () => {
    let val = $("#start-hr").val(); // Get value of start-hr
    if (val.length == 1) { // If single digit
        $("#start-hr").val("0" + val); // Prepend a 0
    }
});

// Do the same for start minute, end hour, and end minute
$("#start-min").on("input", () => {
    let val = $("#start-min").val();
    if (val.length == 1) {
        $("#start-min").val("0" + val);
    }
});

$("#end-hr").on("input", () => {
    let val = $("#end-hr").val();
    if (val.length == 1) {
        $("#end-hr").val("0" + val);
    }
});

$("#end-min").on("input", () => {
    let val = $("#end-min").val();
    if (val.length == 1) {
        $("#end-min").val("0" + val);
    }
});

// Get the forecast with the set variables and update graph with results
function getForecast() {
    // Send a GET request to the forecast API
    $.ajax({
        url: '/backend/API/Model/forcast.php',
        type: 'GET',
        data: {
            // weather: $("#weather").val(),
            // category: $("category").val(),
            // Format time as XX:XX
            startTime: `${$("#start-hr").val()}:${$("#start-min").val()}`,
            endTime: `${$("#end-hr").val()}:${$("#end-min").val()}`,
            minDiscount: $("#min-discount").val(),
            maxDiscount: $("#max-discount").val()
        },
        success: data => {
            // Get the highest bar on the bar chart
            let maxValue = getMaxValue([
                data["AvgMondayCollected"] + data["AvgMondayNoShow"],
                data["AvgTuesdayCollected"] + data["AvgTuesdayNoShow"],
                data["AvgWednesdayCollected"] + data["AvgWednesdayNoShow"],
                data["AvgThursdayCollected"] + data["AvgThursdayNoShow"],
                data["AvgFridayCollected"] + data["AvgFridayNoShow"],
                data["AvgSaturdayCollected"] + data["AvgSaturdayNoShow"],
                data["AvgSundayCollected"] + data["AvgSundayNoShow"]
            ]);

            let maxAxisValue = 10; // Y axis will default go up to 10
            if (maxValue > 10) { // If the highest bar is more than 10
                // Y axis is maxValue rounded to the nearest 10
                maxAxisValue = Math.ceil(maxValue / 10) * 10;
            }
            graph.generateYAxisLabels(0, maxAxisValue); // Generate Y axis values

            // Plot show and no show on the bar chart
            graph.plotBar("Monday", data["AvgMondayCollected"], "Show");
            graph.plotBar("Monday", data["AvgMondayNoShow"], "No Show");
            graph.plotBar("Tuesday", data["AvgTuesdayCollected"], "Show");
            graph.plotBar("Tuesday", data["AvgTuesdayNoShow"], "No Show");
            graph.plotBar("Wednesday", data["AvgWednesdayCollected"], "Show");
            graph.plotBar("Wednesday", data["AvgWednesdayNoShow"], "No Show");
            graph.plotBar("Thursday", data["AvgThursdayCollected"], "Show");
            graph.plotBar("Thursday", data["AvgThursdayNoShow"], "No Show");
            graph.plotBar("Friday", data["AvgFridayCollected"], "Show");
            graph.plotBar("Friday", data["AvgFridayNoShow"], "No Show");
            graph.plotBar("Saturday", data["AvgSaturdayCollected"], "Show");
            graph.plotBar("Saturday", data["AvgSaturdayNoShow"], "No Show");
            graph.plotBar("Sunday", data["AvgSundayCollected"], "Show");
            graph.plotBar("Sunday", data["AvgSundayNoShow"], "No Show");

            // Draw graph
            graph.draw();
        }
    });
}

getForecast();

$("#update-btn").click(getForecast); // If update button clicked, re-run get forecast
