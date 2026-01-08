/**
 * Compliant with AI-GUIDELINES.md v1.0
 *
 * Quicksort implementation in JavaScript.
 */

/**
 * Sorts an array using the Quicksort algorithm.
 *
 * @param {Array} arr - The array to sort.
 * @returns {Array} - The sorted array.
 */
export function quickSort(arr) {
    if (arr.length <= 1) {
        return arr;
    }

    const pivot = arr[Math.floor(arr.length / 2)];
    const left = [];
    const right = [];
    const equal = [];

    for (const element of arr) {
        if (element < pivot) {
            left.push(element);
        } else if (element > pivot) {
            right.push(element);
        } else {
            equal.push(element);
        }
    }

    return [...quickSort(left), ...equal, ...quickSort(right)];
}
