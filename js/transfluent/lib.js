/**
 * build select options from object
 *
 * @param obj
 *
 * @returns string
 */
function buildSelectOptions(obj) {
    var html = "";
    for (var i = 0; i < obj.length; i++) {
        html += '<option ';
        html += (typeof(obj[i].name) !== 'undefined') ? " name=\"" + obj[i].name + "\" " : " ";
        html += (typeof(obj[i].selected) !== 'undefined' && true == obj[i].selected) ? " selected='" + obj[i].selected + "' " : " ";
        html += ">";
        html += (typeof(obj[i].name) !== 'undefined') ? obj[i].name : "";
        html += '</option>\n';
    }
    return html;
}

/**
 * hides element
 *
 * @param id
 *
 * @returns {boolean}
 */
function hideElementById(id) {
    var element = document.getElementById(id);
    element.style.display = 'none';
    return true;
}

/**
 * show a dom element
 *
 * @param id
 *
 * @returns {boolean}
 */
function showElementById(id) {
    var element = document.getElementById(id);
    if (!element)
        return false;

    element.style.display = null;
    return true;
}

/**
 * returns array of products from selected groups
 *
 * @returns {Array}
 */
function getSelectedProducts(checkboxGroup) {
    var productIds = [];
    var checkboxes = document.getElementsByName(checkboxGroup);
    var categoryId, products;
    for (var i = 0; i < checkboxes.length; i++) {

        if (checkboxes[i].checked == true) {
            categoryId = checkboxes[i].value;
            for (var j = 0; j < categoryProducts[categoryId].length; j++)
                productIds.push(categoryProducts[categoryId][j]);
        }
    }
    return productIds;
}