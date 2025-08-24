class BehaviorTracker {
    constructor() {
        this.mouseMovements = [];
        this.clickPatterns = [];
        this.scrollEvents = [];
        this.startTime = Date.now();
        
        this.initTracking();
    }

    initTracking() {
        // Отслеживание движения мыши
        document.addEventListener('mousemove', (e) => {
            this.mouseMovements.push({
                x: e.clientX,
                y: e.clientY,
                timestamp: Date.now(),
                speed: this.calculateMovementSpeed(e)
            });
        });

        // Отслеживание кликов
        document.addEventListener('click', (e) => {
            this.clickPatterns.push({
                element: e.target.tagName,
                position: {x: e.clientX, y: e.clientY},
                timestamp: Date.now(),
                pressure: e.pressure || 0
            });
        });

        // Отслеживание скролла
        document.addEventListener('scroll', (e) => {
            this.scrollEvents.push({
                position: window.scrollY,
                speed: this.calculateScrollSpeed(),
                timestamp: Date.now()
            });
        });

        // Отслеживание ввода данных
        const inputs = document.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.trackInputBehavior(e.target);
            });
        });
    }

    calculateMovementSpeed(e) {
        // Расчет скорости движения мыши
        if (this.lastMouseEvent) {
            const distance = Math.sqrt(
                Math.pow(e.clientX - this.lastMouseEvent.x, 2) + 
                Math.pow(e.clientY - this.lastMouseEvent.y, 2)
            );
            const timeDiff = e.timeStamp - this.lastMouseEvent.timeStamp;
            return distance / (timeDiff / 1000);
        }
        this.lastMouseEvent = {x: e.clientX, y: e.clientY, timeStamp: e.timeStamp};
        return 0;
    }

    getBehaviorScore() {
        // Анализ собранных данных и вычисление score
        const score = this.analyzePatterns();
        return Math.min(Math.max(score, 0), 100);
    }

    analyzePatterns() {
        let score = 50;
        
        // Анализ движения мыши (боты часто двигаются по прямым линиям)
        if (this.mouseMovements.length > 10) {
            const linearity = this.calculateMouseLinearity();
            score += linearity > 0.8 ? -20 : 10;
        }

        // Анализ скорости кликов (боты часто кликают слишком быстро)
        if (this.clickPatterns.length > 0) {
            const clickSpeed = this.calculateClickSpeed();
            score += clickSpeed > 1000 ? -15 : 5;
        }

        return score;
    }
}