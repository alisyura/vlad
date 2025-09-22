/**
 * Класс для управления счетчиком символов в текстовом поле.
 */
class CharCounter {
    constructor(textareaId, counterSelector, maxLength = 5000) {
        this.textarea = document.getElementById(textareaId);
        this.counter = document.querySelector(counterSelector);
        this.maxLength = maxLength;

        if (this.textarea && this.counter) {
            this.init();
        }
    }

    init() {
        this.updateCounter();
        this.textarea.addEventListener('input', () => this.updateCounter());
    }

    updateCounter() {
        this.counter.textContent = `${this.textarea.value.length} / ${this.maxLength}`;
    }
}
