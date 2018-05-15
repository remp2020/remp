import { select } from 'optimal-select';

(function(window, document, campaign, undefined) {
  // initialize variables for elements that are about to be injected
  var hoveredOverlayElement,
    hoveredOverlayNameElement,
    selectedOverlayElement,
    selectedOverlayNameElement,
    selectedMenuElement,
    clearSelectionElement,
    generatedSelectorWrapperElement,
    selectorInputElement,
    generatedSelectorElement,
    matchingNumberElement,
    injectBannerElement,
    bannerInsertionSettingsModalElement,
    bannerInsertionSettingsModalCancelElement,
    bannerInsertionSettingsModalAcceptElement,
    bannerInsertionSettingsSelectElement,
    cancelAndReturnToRempElement,
    saveAndReturnToRempElement;

  // window reference from which this site was open
  var rempParentWebsite;
  var allowedOrigin = campaign.url;

  // markup for injected toolbar
  var toolbarMarkup = `
    <div class="remp">
      <div id="hovered-overlay" class="hovered-overlay">
        <span class="hovered-overlay__name" id="hovered-overlay-name"></span>
      </div>
      <div id="selected-overlay" class="selected-overlay">
        <span class="selected-overlay__name" id="selected-overlay-name"></span>
      </div>
      <div id="selected-menu" class="selected-menu">
        <div class="selected-menu__item" id="inject-banner">
          <img src="assets/img/bannerSelector/insert-banner.svg" class="selected-menu__icon" alt="Insert Banner">
          <span class="selected-menu__label">Inject Banner</span>
        </div>
        <div class="selected-menu__item" id="clear-selection">
          <img src="assets/img/bannerSelector/delete.svg" class="selected-menu__icon" alt="Delete">
          <span class="selected-menu__label">Clear selection</span>
        </div>
      </div>
      <div class="remp-toolbar" id="remp-toolbar">
        <a href="" class="remp-toolbar__logo-wrapper">
          <img src="assets/img/bannerSelector/logo.svg" alt="REMP" class="remp-toolbar__logo"> </a>
        <button class="remp-toolbar__selector-wrapper" id="generated-selector-wrapper">
          <span class="remp-toolbar__selector" id="generated-selector"></span>
          <input type="text" class="remp-toolbar__input-selector" id="selector-input">
          <span class="remp-toolbar__matching-elements" id="matching-elements-number">0</span>
        </button>
        <div class="remp-toolbar__show-parents-wrapper" id="toggle-parents-list">
          <img src="assets/img/bannerSelector/parents.svg" class="remp-toolbar__show-parents" alt="Show Parents">
          <div class="remp-toolbar__parents">
            <h5 class="remp-toolbar__parents__title">Parent elements</h5>
            <ul class="remp-toolbar__parents__list" id="parents-list">
              <li class="remp-toolbar__parents__item"></li>
            </ul>
          </div>
        </div>
        <a href="#" class="remp-toolbar__cancel" id="cancelAndReturnToRemp">Cancel</a>
        <a href="#" class="remp-toolbar__confirm" id="saveAndReturnToRemp">Confirm</a>
      </div>
      <div class="remp-modal" id="banner-settings-modal">
        <div class="remp-modal__content">
          <div class="remp-modal__body">
            <h3 class="remp-modal__title">How do you want to inject the banner?</h3>
            <select class="remp-modal__select" id="banner-placement">
              <option value="before">Inject before selected element</option>
              <option value="after">Inject after selected element</option>
              <option value="first">Inject inside as the first child</option>
              <option value="last">Inject inside as the last child</option>
            </select>
          </div>
          <div class="remp-modal__footer">
            <a href="#" class="remp-modal__inject" id="banner-settings-modal-accept">Inject</a>
            <a href="#" class="remp-modal__cancel" id="banner-settings-modal-cancel">Cancel</a>
          </div>
        </div>
      </div>
    </div>
  `;
  // styles for injected toolbar
  var toolbarStyles = `
    <style>
    .remp {
      position: static;
      font-family: sans-serif;
    }
  
    .remp-toolbar {
      z-index: 999999;
      /* 6 */
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 50px;
      background-color: #00acc1;
      display: flex;
      align-items: center;
    }
  
    .selected-overlay,
    .hovered-overlay {
      z-index: 9999;
      /* 4 */
      top: -9999px;
      position: absolute;
      width: 0;
      height: 0;
      border: 2px solid #02acc1;
      background-color: rgba(2, 172, 193, 0.07);
      pointer-events: none;
    }
  
    .selected-overlay {
      border: 2px solid black;
      background-color: rgba(0, 0, 0, 0.07);
    }
  
    .selected-overlay__name,
    .hovered-overlay__name {
      position: absolute;
      background-color: #02acc1;
      top: -22px;
      left: -2px;
      color: white;
      padding: 2px 7px;
      font-size: 12px;
    }
  
    .selected-overlay__name {
      background-color: black;
    }
  
    .selected-menu {
      z-index: 99999;
      /* 5 */
      background-color: white;
      box-shadow: 1px 3px 17px 0 rgba(170, 171, 175, 0.55);
      position: absolute;
      display: none;
    }
  
    .selected-menu__item {
      padding: 10px 15px;
      display: flex;
    }
  
    .selected-menu__item:hover {
      background-color: #02acc1;
      background-color: rgb(240, 240, 240);
      cursor: pointer;
    }
  
    .selected-menu__icon {
      height: 20px;
    }
  
    .selected-menu__label {
      margin-left: 10px;
    }
  
    .remp-toolbar__logo-wrapper {
      width: 120px;
      height: 50px;
      background-color: black;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  
    .remp-toolbar__logo-wrapper:hover {
      background-color: #222222;
    }
  
    .remp-toolbar__logo {
      height: 25px;
      transition: transform 0.2s ease-in-out;
      transform: scale(1);
    }
  
    .remp-toolbar__logo-wrapper:hover .remp-toolbar__logo {
      transform: scale(1.02);
    }
  
    .remp-toolbar__selector-wrapper {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex: 1;
      height: 50px;
      padding: 0 25px;
      background-color: #00acc1;
      border: none;
    }
  
    .remp-toolbar__selector-wrapper:hover {
      cursor: pointer;
      background-color: #019caf;
    }
  
    .remp-toolbar__selector {
      font-size: 14px;
      text-align: left;
    }
  
    .remp-toolbar__input-selector {
      display: none;
      font-size: 14px;
      height: 46px;
      margin-right: 20px;
      margin-left: -25px;
      flex: 1;
      border: 0;
      padding: 0;
      padding-left: 25px;
      outline: 0;
      box-shadow: none;
    }
  
    .remp-toolbar__selector-wrapper.show-input {
      background-color: white;
      border-bottom: 2px solid #00acc1;
      border-left: 2px solid #00acc1;
      border-top: 2px solid #00acc1;
    }
  
    .remp-toolbar__selector-wrapper.show-input .remp-toolbar__selector {
      display: none;
    }
  
    .remp-toolbar__selector-wrapper.show-input .remp-toolbar__input-selector {
      display: block;
    }
  
    .remp-toolbar__matching-elements {
      text-align: center;
      background-color: black;
      color: white;
      font-size: 12px;
      border-radius: 100%;
      width: 25px;
      height: 25px;
      line-height: 25px;
    }
  
    .remp-toolbar__matching-elements:empty {
      display: none;
    }
  
    .remp-toolbar__show-parents-wrapper {
      display: flex;
      align-items: center;
      height: 50px;
      padding: 0 25px;
      border: none;
      border-left: 1px solid #04a0b3;
      background-color: #00acc1;
    }
  
    .remp-toolbar__show-parents-wrapper:hover {
      cursor: pointer;
      background-color: #019caf;
    }
  
    .remp-toolbar__show-parents {
      height: 20px;
    }
  
    .remp-toolbar__parents {
      z-index: 99999;
      /* 5 */
      display: none;
      position: absolute;
      top: 45px;
      background-color: white;
      box-shadow: 1px 3px 17px 0 rgba(170, 171, 175, 0.55);
      border-radius: 3px;
      transform: translateX(-50%);
      width: 300px;
    }
  
    .remp-toolbar__show-parents-wrapper.active .remp-toolbar__parents {
      display: block;
    }
  
    .remp-toolbar__parents__title {
      margin: 0;
      padding: 10px;
      border-bottom: 1px solid rgba(128, 128, 128, 0.18);
      text-transform: uppercase;
      font-size: 12px;
      color: #595555;
    }
  
    .remp-toolbar__parents__list {
      max-height: 300px;
      overflow: scroll;
      margin: 0;
      padding: 0;
      list-style-type: none;
    }
  
    .remp-toolbar__parents__item {
      margin: 0;
      padding: 13px 10px;
      background-color: white;
      border: none;
      text-align: left;
      width: 100%;
      font-size: 13px;
    }
  
    .remp-toolbar__parents__item:hover {
      background-color: #00acc1;
      cursor: pointer;
    }
  
    .remp-toolbar__cancel,
    .remp-toolbar__confirm {
      height: 50px;
      color: black;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 25px;
      border-left: 1px solid #04a0b3;
      text-decoration: none;
    }
  
    .remp-toolbar__cancel:hover,
    .remp-toolbar__cancel:focus {
      background-color: #019caf;
      color: black;
      text-decoration: none;
    }
  
    .remp-toolbar__confirm {
      background-color: black;
      color: white;
    }
  
    .remp-toolbar__confirm:hover,
    .remp-toolbar__confirm:focus {
      background-color: #222222;
      color: white;
      text-decoration: none;
    }
  
    .hovered {
      border: 2px solid rgba(255, 0, 0, 0.8);
      background-color: rgba(255, 0, 0, 0.01);
    }
  
    body:not(.remp-toolbar) {
      cursor: crosshair !important;
    }
  
    .remp-modal {
      display: none;
      position: fixed;
      z-index: 9999999;
      /* 7 */
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.7);
      align-items: center;
      justify-content: center;
      cursor: auto !important;
    }
  
    .remp-modal__content {
      position: relative;
      background-color: #fefefe;
      margin: auto;
      padding: 25px;
      max-width: 350px;
      width: 80%;
      box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
      /* box-shadow: 1px 3px 17px 0 rgba(170, 171, 175, 0.55); */
      animation-name: animatetop;
      animation-duration: 0.4s;
      text-align: center;
    }
  
    @keyframes animatetop {
      from {
        top: -50px;
        opacity: 0;
      }
      to {
        top: 0;
        opacity: 1;
      }
    }
  
    .remp-modal__title {
      font-weight: 500;
      font-size: 17px;
      margin-bottom: 20px;
    }
  
    .remp-modal__select {
      margin: 10px 0 30px 0;
      -webkit-appearance: none;
      border: 1px solid black;
      background: white;
      padding: 5px 40px;
    }
  
    .remp-modal__inject {
      color: white;
      background-color: #00acc1;
      padding: 10px 40px;
      margin-right: 30px;
      display: inline-block;
    }
  
    .remp-modal__inject:hover,
    .remp-modal__inject:focus {
      text-decoration: none;
      background-color: #019caf;
      color: white;
    }
  
    .remp-modal__cancel {
      color: #878787;
    }
  
    .remp-modal__cancel:hover,
    .remp-modal__cancel:focus {
      color: #666666;
      text-decoration: none;
    }
  
    /* demo styles */
  
    body {
      padding-top: 100px !important;
    }
  </style>
  `;

  // public functions
  window.remplib.campaign.bannerSelector = {
    init: function() {
      initializeBannerSelector();
    }
  };

  // private functions
  function initializeBannerSelector() {
    injectControlsMarkup();
    injectControlsStyles();
    attachEventListeners();
  }

  function injectControlsMarkup() {
    document.querySelector('body').insertAdjacentHTML('afterbegin', toolbarMarkup);

    hoveredOverlayElement = document.getElementById('hovered-overlay');
    hoveredOverlayNameElement = document.getElementById('hovered-overlay-name');

    selectedOverlayElement = document.getElementById('selected-overlay');
    selectedOverlayNameElement = document.getElementById(
      'selected-overlay-name'
    );
    selectedMenuElement = document.getElementById('selected-menu');
    clearSelectionElement = document.getElementById('clear-selection');
    generatedSelectorWrapperElement = document.getElementById(
      'generated-selector-wrapper'
    );
    selectorInputElement = document.getElementById('selector-input');
    generatedSelectorElement = document.getElementById('generated-selector');
    matchingNumberElement = document.getElementById('matching-elements-number');
    injectBannerElement = document.getElementById('inject-banner');
    bannerInsertionSettingsModalElement = document.getElementById(
      'banner-settings-modal'
    );
    bannerInsertionSettingsModalCancelElement = document.getElementById(
      'banner-settings-modal-cancel'
    );
    bannerInsertionSettingsModalAcceptElement = document.getElementById(
      'banner-settings-modal-accept'
    );
    bannerInsertionSettingsSelectElement = document.getElementById(
      'banner-placement'
    );
    cancelAndReturnToRempElement = document.getElementById(
      'cancelAndReturnToRemp'
    );
    saveAndReturnToRempElement = document.getElementById('saveAndReturnToRemp');
  }

  function injectControlsStyles() {
    document.querySelector('body').insertAdjacentHTML('afterbegin', toolbarStyles);
  }

  function attachEventListeners() {
    bannerInsertionSettingsModalAcceptElement.addEventListener(
      'click',
      injectBanner
    );

    injectBannerElement.addEventListener(
      'click',
      showBannerInsertionSettingsModal
    );

    bannerInsertionSettingsModalCancelElement.addEventListener(
      'click',
      hideBannerInsertionSettingsModal
    );

    document.addEventListener('click', event => {
      if (event.target == bannerInsertionSettingsModalElement) {
        hideBannerInsertionSettingsModal();
        return false;
      }

      if (isPartOfREMPToolbar(event.target)) return false;

      event.stopPropagation();
      event.preventDefault();

      handleSelectionOfElement(event.target);
    });

    document.addEventListener('mouseover', event => {
      if (isPartOfREMPToolbar(event.target)) return false;

      makeHoveredOverlay(event.target);
    });

    document.addEventListener('mouseout', event => {
      event.target.classList.remove('hovered');
    });

    document
      .getElementById('toggle-parents-list')
      .addEventListener('click', event => {
        if (event.target.matches('.remp-toolbar__parents *')) return false;

        toggleParentsListVisibility();
      });

    clearSelectionElement.addEventListener('click', clearSelection);

    generatedSelectorWrapperElement.addEventListener(
      'click',
      showSelectorInput
    );

    generatedSelectorWrapperElement.addEventListener(
      'mouseleave',
      hideSelectorInput
    );

    selectorInputElement.addEventListener('keyup', function(event) {
      event.preventDefault();

      var keycode =
        typeof event.keyCode != 'undefined' && event.keyCode
          ? event.keyCode
          : event.which;

      switch (keycode) {
        case 27:
          hideSelectorInput();
          break;
        case 13:
          acceptSelectorInput();
          break;
        default:
          console.log(this.value);
      }
    });

    window.addEventListener('message', receiveKeepAliveMessage, false);

    cancelAndReturnToRempElement.addEventListener(
      'click',
      cancelAndReturnToRemp
    );

    saveAndReturnToRempElement.addEventListener('click', saveAndReturnToRemp);
  }

  function cancelAndReturnToRemp() {
    if (window.opener) {
      window.close();
    } else {
      alert('This page was not opened from REMP.');
    }
  }

  function saveAndReturnToRemp() {
    var generatedSelector = generatedSelectorElement.innerText;
    if (window.opener) {
      if (generatedSelector.length) {
        rempParentWebsite.postMessage(
          { selector: generatedSelectorElement.innerText },
          allowedOrigin
        );
        window.close();
      } else {
        alert('Please select an element');
      }
    } else {
      alert('This page was not opened from REMP.');
    }
  }

  function receiveKeepAliveMessage(event) {
    if (event.origin !== allowedOrigin) return;

    rempParentWebsite = event.source;
  }

  function showBannerInsertionSettingsModal() {
    bannerInsertionSettingsModalElement.style.display = 'flex';
  }

  function hideBannerInsertionSettingsModal() {
    bannerInsertionSettingsModalElement.style.display = 'none';
  }

  function acceptSelectorInput() {
    var writtenSelector = selectorInputElement.value;

    try {
      handleSelectionOfElement(
        document.querySelector(writtenSelector),
        writtenSelector
      );
      hideSelectorInput();
    } catch (err) {
      clearSelection();
      alert('There are no elements with that selector on this page.');
    }
  }

  function showSelectorInput() {
    generatedSelectorWrapperElement.classList.add('show-input');
    selectorInputElement.value = generatedSelectorElement.innerText;
    selectorInputElement.focus();
  }

  function hideSelectorInput() {
    generatedSelectorWrapperElement.classList.remove('show-input');
  }

  function clearSelection() {
    writeToGeneratedSelectorBar('');
    writeToNumberOfSelectedElementsCount(0);
    listArrayOfParentsSelectors([]);
    clearSelectedOverlay();
    hideSelectedMenu();
  }

  function handleSelectionOfElement(element, selector) {
    if (!selector) {
      selector = select(element);
    }

    if (document.querySelectorAll(selector).length > 1) {
      alert(
        'There are multiple elements with that selector on this page. Selector has to be unique.'
      );
      clearSelection();
      return false;
    }

    writeToGeneratedSelectorBar(selector);

    writeToNumberOfSelectedElementsCount(
      document.querySelectorAll(selector).length
    );

    var parents = getParents(element);
    var parentsSelectors = getUniqueSelectorsForElementArray(parents);
    listArrayOfParentsSelectors(parentsSelectors);

    makeSelectedOverlay(element);

    // This shows the context menu, which allows you to preview inject the banner
    // We are not currently finishing this feature
    // showSelectedMenu(element);
  }

  function writeToGeneratedSelectorBar(text) {
    generatedSelectorElement.innerText = text;
  }

  function writeToNumberOfSelectedElementsCount(text) {
    matchingNumberElement.innerText = text;
  }

  function handleParentsListItemHover() {
    makeHoveredOverlay(document.querySelector(this.innerText));
  }

  function handleParentsListItemClick(event) {
    event.stopPropagation();
    var selector = this.innerText;
    handleSelectionOfElement(document.querySelector(selector), selector);
  }

  function makeHoveredOverlay(element) {
    var measurements = getElementsDimensionsAndPosition(element);

    hoveredOverlayElement.style.left = measurements.left + 'px';
    hoveredOverlayElement.style.top = measurements.top + 'px';
    hoveredOverlayElement.style.width = measurements.width + 'px';
    hoveredOverlayElement.style.height = measurements.height + 'px';

    hoveredOverlayNameElement.innerText = element.tagName.toLowerCase();
  }

  function makeSelectedOverlay(element) {
    var measurements = getElementsDimensionsAndPosition(element);

    selectedOverlayElement.style.left = measurements.left + 'px';
    selectedOverlayElement.style.top = measurements.top + 'px';
    selectedOverlayElement.style.width = measurements.width + 'px';
    selectedOverlayElement.style.height = measurements.height + 'px';

    selectedOverlayNameElement.innerText = element.tagName.toLowerCase();
  }

  function clearSelectedOverlay() {
    selectedOverlayElement.style.left = -9999 + 'px';
    selectedOverlayElement.style.top = -9999 + 'px';
    selectedOverlayElement.style.width = 0;
    selectedOverlayElement.style.height = 0;

    selectedOverlayNameElement.innerText = '';
  }

  function showSelectedMenu(element) {
    var measurements = getElementsDimensionsAndPosition(element);

    selectedMenuElement.style.left = measurements.left + 'px';
    selectedMenuElement.style.top =
      measurements.top + measurements.height + 'px';
  }

  function hideSelectedMenu() {
    selectedMenuElement.style.left = -9999 + 'px';
    selectedMenuElement.style.top = -9999 + 'px';
  }

  function getElementsDimensionsAndPosition(element) {
    element = element.getBoundingClientRect();
    return {
      left: element.left + window.scrollX,
      top: element.top + window.scrollY,
      width: element.width,
      height: element.height
    };
  }
  function getParents(element) {
    var parents = [];
    for (; element && element !== document; element = element.parentNode) {
      parents.push(element);
    }
    return parents;
  }
  function getUniqueSelectorsForElementArray(elements) {
    var selectors = [];
    elements.forEach(function(currentElement) {
      selectors.push(select(currentElement));
    });
    return selectors;
  }
  function listArrayOfParentsSelectors(parents) {
    var parentsList = document.getElementById('parents-list');
    parentsList.innerHTML = '';

    parents.forEach(function(currentParent) {
      var parentItem = document.createElement('li');
      parentItem.classList.add('remp-toolbar__parents__item');
      parentItem.innerText = currentParent;
      parentItem.addEventListener('mouseover', handleParentsListItemHover);
      parentItem.addEventListener('click', handleParentsListItemClick);

      parentsList.appendChild(parentItem);
    });
  }
  function isPartOfREMPToolbar(element) {
    return hasSomeParentTheClass(element, 'remp');
  }
  function toggleParentsListVisibility() {
    document.getElementById('toggle-parents-list').classList.toggle('active');
  }
  function hasSomeParentTheClass(element, classname) {
    if (
      element.className &&
      element.className.split(' ').indexOf(classname) >= 0
    )
      return true;
    return (
      element.parentNode && hasSomeParentTheClass(element.parentNode, classname)
    );
  }
  function injectBanner() {
    hideBannerInsertionSettingsModal();
    alert('TODO, insert markup');
    var bannerMarkup =
      '<div style="background-color: red; color: white; width: 200px; height: 200px;"><h1>Lorem</h1> <p>Bacon ipsum dolor amet tail kielbasa turkey pancetta short ribs ham hock salami landjaeger meatball bacon capicola drumstick tenderloin.</p></div>';

    // TODO: refactor get generated selector and generated seelctor element in all file
    // document
    //   .querySelector(generatedSelectorElement.innerText)
    //   .insertAdjacentHTML('beforebegin', bannerMarkup);

    // https://stackoverflow.com/a/19316351

    // style select

    // hide highlighters

    // disable banner highlighting

    // after banner placement something like try again

    // switch (bannerInsertionSettingsSelectElement.value) {
    //   case 'before':
    //     break;
    //   case 'after':
    //     break;
    //   case 'first':
    //     break;
    //   case 'last':
    //     break;
    // }
  }
})(window, document, remplib.campaign);

remplib.campaign.bannerSelector.init();