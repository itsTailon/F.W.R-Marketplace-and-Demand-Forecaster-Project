class Graph {
    constructor(type, width, height, rows, cols, padding) {
        this.type = type;
        this.width = width;
        this.height = height;
        this.rows = rows;
        this.cols = cols;
        this.padding = padding;

        this.xAxisLabels = [];
        this.yAxisLabels = [];

        this.colWidth = (width - 2*padding) / cols;
        this.rowHeight = (height - 2*padding) / rows;

        this.canvas = document.getElementById("graph");
        this.canvas.width = width;
        this.canvas.height = height;
        this.ctx = this.canvas.getContext("2d");

        this.keys = {};
        this.keySpace = 0;

        this.plot = new Map();
    }

    generateAxisLabels(start, end, divisor) {
        let axisLabels = []
        for (let i = start; i <= end; i += (end-start)/divisor) {
            axisLabels.push(Math.round(i));
        }
        return axisLabels;
    }

    generateXAxisLabels(start, end) {
        this.setXAxisLabels(this.generateAxisLabels(start, end, this.cols));
    }
    
    generateYAxisLabels(start, end) {
        this.setYAxisLabels(this.generateAxisLabels(start, end, this.rows));
    }

    setXAxisLabels(labels) {
        this.xAxisLabels = labels;
        this.xAxisLabels.forEach(label => {
            if (!this.plot.has(label)) {
                this.plot.set(label, new Map());
            }
        });
    }

    setYAxisLabels(labels) {
        this.yAxisLabels = labels;
    }

    draw() {
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

            this.xAxisLabels.forEach(label => {
                if (this.plot.has(label)) {
                    let col = this.plot.get(label);

                    let y = this.height - this.padding - this.keySpace;

                    Object.keys(this.keys).forEach(key => {
                        if (col.has(key)) {
                            let colour = this.keys[key];
                            let val = col.get(key);
                            this.ctx.fillStyle = colour;
                            this.ctx.fillRect(x - 0.3*this.colWidth, y - (yAxisScale*val), 0.6*this.colWidth, (yAxisScale*val));
                            this.ctx.fillStyle = "#000000";
                            this.ctx.fillText(`${val}`, x,  y - (yAxisScale*(val-2)));
                            y -= (yAxisScale*val);
                        }
                    });
                }
                x += this.colWidth;
                
            })
        }
    }

    addKey(colour, text) {
        if (this.type === "bar") {
            this.keys[text] = colour;
            this.height += 20;
            this.keySpace += 20;
            this.canvas.height = this.height;
            this.ctx = this.canvas.getContext("2d");
        }
    }

    plotBar(xVal, yVal, key) {
        let column = new Map();
        if (this.plot.has(xVal)) {
            column = this.plot.get(xVal);
        }
        column.set(key, yVal);
        this.plot.set(xVal, column);
    }
};

const graph = new Graph("bar", 1000, 500, 8, 7, 50);
graph.setXAxisLabels(["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]);
graph.generateYAxisLabels(0, 200);
graph.addKey("#3393dc", "Show");
graph.addKey("#f69d0d", "No Show");
graph.plotBar("Monday", 65, "Show");
graph.plotBar("Monday", 21, "No Show");
graph.plotBar("Tuesday", 8, "Show");
graph.plotBar("Tuesday", 48, "No Show");
graph.plotBar("Wednesday", 90, "Show");
graph.plotBar("Wednesday", 40, "No Show");
graph.plotBar("Thursday", 81, "Show");
graph.plotBar("Thursday", 19, "No Show");
graph.plotBar("Friday", 56, "Show");
graph.plotBar("Friday", 96, "No Show");
graph.plotBar("Saturday", 55, "Show");
graph.plotBar("Saturday", 27, "No Show");
graph.plotBar("Sunday", 40, "Show");
graph.plotBar("Sunday", 99, "No Show");

graph.draw();
