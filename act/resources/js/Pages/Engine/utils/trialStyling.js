import { computed } from "vue";
export function getCircularLayoutStyles(
    numTargets,
    radius = 40,
    theme = 1,
    isLandscape = false,
    hasCanUnselectTargets = false,
    verticalTargetsOnPhone = false,
    isPhone = false,
    deviceType
) {
    const styles = [];

    if (theme === 2) {
        const portraitLayouts = {
            2: [
                { left: 10.4, top: 44 },
                { left: 77.6, top: 44 },
            ],
            3: [
                { left: 20, top: 68 },
                { left: 44, top: 77.5 },
                { left: 68, top: 68 },
            ],
            4: [
                { left: 12.8, top: 58 },
                { left: 30.7, top: 76 },
                { left: 56.5, top: 76 },
                { left: 75, top: 58 },
            ],
            5: [
                { left: 10.4, top: 45 },
                { left: 20, top: 68 },
                { left: 44, top: 77.5 },
                { left: 68, top: 68 },
                { left: 77.6, top: 45 },
            ],
            6: [
                { left: 19.4, top: 20 },
                { left: 10.4, top: 44 },
                { left: 19.4, top: 68 },
                { left: 68.6, top: 68 },
                { left: 77.6, top: 44 },
                { left: 68.6, top: 20 },
            ],
            7: [
                { left: 17.6, top: 21 },
                { left: 10.4, top: 45 },
                { left: 20, top: 68 },
                { left: 44, top: 77.5 },
                { left: 68, top: 68 },
                { left: 77.6, top: 45 },
                { left: 70.4, top: 21 },
            ],
        };

        const landscapeLayouts = {
            2: [
                { left: 1, top: 35 },
                { left: 68.9, top: 35 },
            ],
            3: [
                { left: 11, top: 48.5 },
                { left: 35, top: 58.5 },
                { left: 59, top: 48.5 },
            ],
            4: [
                { left: 3, top: 34.5 },
                { left: 22, top: 53 },
                { left: 48, top: 53 },
                { left: 66.5, top: 34.5 },
            ],
            5: [
                { left: 1, top: 23.5 },
                { left: 11.4, top: 47.5 },
                { left: 35, top: 57.4 },
                { left: 58.6, top: 47.5 },
                { left: 69, top: 23.5 },
            ],
            6: [
                { left: 10, top: 11 },
                { left: 1, top: 35 },
                { left: 10, top: 59 },
                { left: 60, top: 59 },
                { left: 69, top: 35 },
                { left: 60, top: 11 },
            ],
            7: [
                { left: 8, top: 9.5 },
                { left: 1.5, top: 33 },
                { left: 12, top: 55 },
                { left: 35, top: 63.5 },
                { left: 58, top: 55 },
                { left: 68.5, top: 33 },
                { left: 62, top: 9.5 },
            ],
        };

        const layouts = isLandscape ? landscapeLayouts : portraitLayouts;
        const targetLayout = layouts[numTargets] || layouts[7];

        for (let i = 0; i < numTargets; i++) {
            const position =
                targetLayout[i] || targetLayout[targetLayout.length - 1];

            styles.push({
                position: "absolute",
                left: `${position.left}vmin`,
                top: `${position.top}vmin`,
                transform: "translate(-50%, -50%)",
                angle: 0,
            });
        }
        return styles;
    }

    // Theme 1: Original circular arrangement
    // const layout = "nw";
    const cLayout =
        hasCanUnselectTargets ||
        (verticalTargetsOnPhone &&
            (isPhone || deviceType === "android" || deviceType === "iphone"))
            ? "og"
            : "nw";

    const angleStep = (2 * Math.PI) / numTargets;
    let lastTargetAngle = Math.PI / 2;
    if (cLayout === "nw") {
        if (numTargets === 2) {
            lastTargetAngle += angleStep / 2;
        } else if (numTargets === 4) {
            lastTargetAngle += angleStep / 2;
        } else if (numTargets === 6) {
            lastTargetAngle -= angleStep / 2;
        }
    }
    const startAngle = lastTargetAngle - (numTargets - 1) * angleStep;

    for (let i = 0; i < numTargets; i++) {
        const angle = startAngle + i * angleStep;
        const left = 50 + radius * Math.cos(angle);
        const top = 50 + radius * Math.sin(angle);

        styles.push({
            position: "absolute",
            left: `${left}%`,
            top: `${top}%`,
            transform: "translate(-50%, -50%)",
            angle: angle,
        });
    }
    return styles;
}

const isPortrait = () => {
    return window.innerWidth < window.innerHeight;
};

export const generalStyles = {
    backgroundColor: "var(--engine-background-color)",
};

export const trialDescriptiveContainerStyle = {
    width: "var(--descriptive-container-width)",
    maxWidth: "var(--descriptive-container-max-width)",
    paddingLeft: "var(--descriptive-container-padding-x)",
    paddingRight: "var(--descriptive-container-padding-x)",
    paddingTop: "var(--descriptive-container-padding-y)",
    paddingBottom: "var(--descriptive-container-padding-y)",
};

export const trialDescriptiveStyle = {
    fontWeight: "var(--descriptive-font-weight)",
    color: "var(--descriptive-color)",
    fontSize: "var(--descriptive-font-size)",
    lineHeight: "var(--descriptive-line-height)",
    textAlign: "var(--descriptive-text-align)",
    marginTop: "var(--descriptive-margin-top)",
    width: "100%",
};

export const trialHeaderContainerStyle = {
    padding: "var(--header-padding-vertical) var(--header-padding-horizontal)",
    borderRadius: "1vmin",
};

export const trialUnselectModalContainerStyle = {
    width: "var(--unselect-modal-width)",
    height: "var(--unselect-modal-height)",
    borderRadius: "var(--unselect-modal-border-radius)",
    scale: "var(--unselect-modal-scale)",
};

export const trialHeaderStyle = {
    fontWeight: "normal",
    color: "var(--header-text-color)",
    textAlign: "center",
    width: "100%",
    fontSize: "var(--header-font-size)",
    maxWidth: "var(--header-max-width)",
    lineHeight: "var(--header-line-height)",
    borderRadius: "1vmin",
};

export const trialPrimeTargetContainerStyle = {
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    width: "100%",
    height: "100%",
};

export const trialPrimeTargetContainerScaleStyle = (deviceType) => {
    let scaleVarName;
    switch (deviceType) {
        case "ipad":
            scaleVarName = `--prime-target-container-scale-ipad`;
            break;
        case "tablet":
            scaleVarName = `--prime-target-container-scale-tablet`;
            break;
        default:
            scaleVarName = `--prime-target-container-scale`;
    }
    return {
        scale: `var(${scaleVarName})`,
    };
};

export const trialPrimeTargetContainer2Style = {
    position: "relative",
    // margin: "auto",
    marginTop: "var(--wrapper-margin-top)",
    marginBottom: "var(--wrapper-margin-bottom)",
    marginLeft: "auto",
    marginRight: "auto",
};

export const trialPrimeContainerStyle = (
    theme = 1,
    numTargets,
    isLandscape = false
) => {
    const baseStyle = {
        position: "absolute",
        top: "50%",
        left: "50%",
        transform: "translate(-50%, -50%)",
        borderRadius: "50%",
    };

    if (theme === 2) {
        // For theme 2, make the prime circle smaller with a lighter border
        let topPos = "50%";
        if (isLandscape) {
            switch (parseInt(numTargets)) {
                case 2:
                    topPos = "50%";
                    break;
                case 3:
                    topPos = "35%";
                    break;
                case 4:
                    topPos = "30%";
                    break;
                case 5:
                    topPos = "33%";
                    break;
                case 6:
                    topPos = "50%";
                    break;
                case 7:
                    topPos = "43%";
                    break;
            }
        }
        return {
            ...baseStyle,
            border: "var(--prime-border-width) var(--prime-border-style) var(--prime-border-color)",
            // Center the prime
            top: topPos,
        };
    }

    // Theme 1 (original)
    else if (theme === 1) {
        return {
            ...baseStyle,
            border: "var(--prime-border-width) var(--prime-border-style) var(--prime-border-color)",
        };
    } else if (theme === 3) {
        return {};
    }
};

export const targetGridStyle3 = {
    display: "grid",
    gridTemplateColumns: "repeat(var(--target-grid-repeat), 1fr)",
    gap: "var(--target-grid-gap)",
    justifyContent: "center",
    alignItems: "center",
};

export const trialPrimeStyle = {
    fontSize: "var(--prime-font-size)",
    textAlign: "center",
    fontWeight: "var(--prime-font-weight)",
    lineHeight: "var(--prime-line-height)",
    margin: "var(--prime-margin)",
    transition: "all 0.2s ease-in-out",
    textWrapStyle: "var(--prime-text-wrap-style)",
};

export const trialPrimeStyle3 = {
    fontSize: "var(--prime-font-size)",
    textAlign: "center",
    fontWeight: "normal",
    lineHeight: "var(--prime-line-height)",
    margin: "var(--prime-margin)",
    transition: "all 0.2s ease-in-out",
};

export const trialTargetContainerStyle = {
    position: "absolute",
    transform: "translate(-50%, -50%)",
    borderRadius: "50%",
    transition: "left 0.2s ease-out, top 0.2s ease-out, opacity 0.2s ease-out",
};

export const trialTargetContainerStyle3 = {
    // position: "relative",
    // transform: "translate(-50%, -50%)",
    // borderRadius: "50%",
    transition: "left 0.2s ease-out, top 0.2s ease-out, opacity 0.2s ease-out",
};

export const trialTargetStyle = {
    textAlign: "center",
    fontSize: "var(--target-font-size)",
    padding: "var(--target-padding)",
    fontWeight: "var(--target-font-weight)",
    lineHeight: "var(--target-line-height)",
    width: "var(--target-img-width)",
    display: "block",
};

export const primeStyle = {
    width: "var(--prime-width)",
    height: "var(--prime-height)",
    borderRadius: "50%",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    textAlign: "center",
    backgroundColor: "var(--prime-bg-color)",
    zIndex: -1,
    color: "var(--prime-font-color)",
};

export const primeStyle3 = {
    // width: "var(--prime-width)",
    // height: "var(--prime-height)",
    // borderRadius: "50%",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    textAlign: "center",
    backgroundColor: "var(--prime-bg-color)",
    zIndex: -1,
};

export const targetBaseStyle = {
    width: "var(--target-width)",
    height: "var(--target-height)",
    borderRadius: "50%",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    transition: "all 0.1s ease-in-out",
    zIndex: 50,
    backgroundColor: "var(--target-bg-color)",
    color: "var(--target-text-color)",
    border: isPortrait()
        ? `1.2vmin var(--target-border-style) var(--target-border-color)`
        : `0.95vmin var(--target-border-style) var(--target-border-color)`,
};

export const targetBaseStyle3 = {
    width: "var(--target-width)",
    height: "var(--target-height)",
    // borderRadius: "50%",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    transition: "all 0.1s ease-in-out",
    zIndex: 50,
    backgroundColor: "var(--target-bg-color)",
    color: "var(--target-text-color)",
    border: isPortrait()
        ? `1.2vmin var(--target-border-style) var(--target-border-color)`
        : `0.95vmin var(--target-border-style) var(--target-border-color)`,
};

export const targetSelectedStyle = (theme = 1, index, targetCount) => {
    if (theme === 2) {
        return {
            border: isPortrait()
                ? `1.2vmin solid transparent`
                : `0.95vmin solid transparent`,
            backgroundColor: "var(--target-selected-bg-color)",
            color: "var(--target-selected-text-color-theme2)",
        };
    }

    // Theme 1 (original) selected style
    else if (theme === 1) {
        return {
            border: isPortrait()
                ? `1.2vmin solid var(--target-selected-border-color)`
                : `0.95vmin solid var(--target-selected-border-color)`,
            backgroundColor: "var(--target-selected-bg-color)",
            color: "var(--target-selected-text-color)",
        };
    } else if (theme === 3) {
        return {
            border: isPortrait()
                ? `1.2vmin solid var(--target-selected-border-color)`
                : `0.95vmin solid var(--target-selected-border-color)`,
            backgroundColor: "var(--target-selected-bg-color)",
            color: "var(--target-selected-text-color)",
        };
    }
};

export const targetHoverStyle = {
    borderColor: "var(--target-hover-border-color)",
};

export const targetInteractiveStyle = {
    cursor: "pointer",
};

const theme2TargetColors = {
    2: ["#D32F2F", "#15AB67"],
    3: ["#D32F2F", "#FB8C00", "#15AB67"],
    4: ["#D32F2F", "#E75A17", "#FB8C00", "#15AB67"],
    5: ["#D32F2F", "#E75A17", "#FB8C00", "#889C33", "#15AB67"],
    6: ["#D32F2F", "#DE4A27", "#E96623", "#FB8C00", "#A7A843", "#5AA251"],
    7: [
        "#D32F2F",
        "#DE4A27",
        "#E96623",
        "#FB8C00",
        "#A7A843",
        "#5AA251",
        "#15AB67",
    ],
};

// Get theme-specific target styling based on position (used in TrialComponent)
export const getThemeTargetStyle = (index, totalTargets, theme = 1) => {
    if (theme === 2) {
        // For theme 2, colors come from CSS variables so updates reflect live.
        // Variable name: --t2-palette-[count]-[slot]
        const cappedCount = Math.min(totalTargets, 7);
        const targetCount = Math.max(cappedCount, 2);
        const varName = `--t2-palette-${targetCount}-${index + 1}`;

        return {
            border: isPortrait()
                ? `1.2vmin var(--target-border-style) var(${varName})`
                : `0.95vmin var(--target-border-style) var(${varName})`,
            backgroundColor: `var(${varName})`,
            // Allow configurable target font color through CSS var
            color: "var(--target-text-color)",
        };
    }

    // For theme 1, return empty object (styling handled elsewhere)
    return {};
};

export const trialBottomTextContainerStyle = (
    isLandscape,
    deviceType,
    browserType
) => {
    // padding: "1vmin",
    let bottomTextContainerMarginTop;

    if (isLandscape) {
        switch (deviceType) {
            case "ipad":
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-landscape-ipad`;
                break;
            case "tablet":
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-landscape-tablet`;
                break;
            case "android":
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-landscape-android`;
                break;
            case "iphone":
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-landscape-iphone`;
                break;
            default:
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-landscape`;
        }
    } else {
        // Portrait
        switch (deviceType) {
            case "iphone":
            case "android":
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-portrait-${deviceType}-${browserType}`;
                break;
            case "ipad":
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-portrait-ipad-${browserType}`;
                break;
            case "tablet":
                bottomTextContainerMarginTop = `--bottom-text-container-margin-top-portrait-tablet-${browserType}`;
                break;
            default:
                bottomTextContainerMarginTop =
                    "--bottom-text-container-margin-top-portrait";
        }
    }

    return {
        marginTop: `var(${bottomTextContainerMarginTop})`,
    };
};

export const warningStyle = (deviceType) => {
    let marginBottomVarName;
    switch (deviceType) {
        case "ipad":
            marginBottomVarName = `--warning-margin-bottom-ipad`;
            break;
        case "tablet":
            marginBottomVarName = `--warning-margin-bottom-tablet`;
            break;
        default:
            marginBottomVarName = `--warning-margin-bottom`;
    }
    return {
        marginBottom: `var(${marginBottomVarName}, 5vmin)`,
    };
};

export const trialBottomTextStyle = {
    fontStyle: "var(--bottom-text-font-style)",
    color: "var(--bottom-text-color)",
    textAlign: "center",
    fontWeight: "var(--bottom-text-font-weight)",
    fontSize: "var(--bottom-text-font-size)",
    lineHeight: "var(--bottom-text-line-height)",
    width: "var(--bottom-text-width)",
};

export const trialNextButtonStyle = {
    fontSize: "var(--button-font-size)",
    padding: "var(--button-padding-vertical) var(--button-padding-horizontal)",
    color: "var(--button-font-color)",
    backgroundColor: "var(--button-bg-color)",
    fontWeight: "800",
    borderRadius: "1vmin",
    transition: "all 0.2s ease-in-out",
    disabled: "opacity-50 cursor-not-allowed",
    display: "inline-flex",
    alignItems: "center",
    flexDirection: "var(--button-flex-direction)",
    gap: "var(--button-flex-gap)",
};

export const trialNextButtonArrowStyle = {
    width: "var(--button-arrow-width)",
    height: "var(--button-arrow-height)",
    display: "var(--button-arrow-display)",
    transform: "scaleX(var(--button-arrow-transform-x))",
    transformOrigin: "center",
};

export const trialNextButtonContainerStyle = (isLandscape) => {
    const style = {
        paddingBottom: "var(--button-margin-bottom)",
    };

    if (isLandscape) {
        style.marginTop = "var(--button-margin-top-landscape)";
    }

    return style;
};

export const getGridLayout = (
    isLandscape,
    isIphoneLandscape,
    deviceType,
    browserType,
    isEditorMode
) => {
    let gridTemplateRowsVarName;

    if (isLandscape) {
        // gridTemplateRowsVarName = isIphoneLandscape
        //     ? "--layout-grid-rows-landscape-iphone"
        //     : "--layout-grid-rows-landscape";
        switch (deviceType) {
            case "ipad":
                gridTemplateRowsVarName = `--layout-grid-rows-landscape-ipad`;
                break;
            case "tablet":
                gridTemplateRowsVarName = `--layout-grid-rows-landscape-tablet`;
                break;
            case "android":
                gridTemplateRowsVarName = `--layout-grid-rows-landscape-android`;
                break;
            case "iphone":
                gridTemplateRowsVarName = `--layout-grid-rows-landscape-iphone`;
                break;
            default:
                gridTemplateRowsVarName = `--layout-grid-rows-landscape`;
        }
    } else {
        // Portrait
        switch (deviceType) {
            case "iphone":
            case "android":
                gridTemplateRowsVarName = `--layout-grid-rows-portrait-${deviceType}-${browserType}`;
                break;
            case "ipad":
                gridTemplateRowsVarName = `--layout-grid-rows-portrait-iPad-${browserType}`;
                break;
            case "tablet":
                gridTemplateRowsVarName = `--layout-grid-rows-portrait-tablet-${browserType}`;
                break;
            default:
                gridTemplateRowsVarName = "--layout-grid-rows-portrait";
        }
    }

    // Fallback if a specific variable isn't found in the stylesheet.
    // This can happen if a device/browser combination is selected in the editor
    // but doesn't have a corresponding variable defined.
    // const rootStyles = getComputedStyle(document.documentElement);
    // if (
    //     isEditorMode &&
    //     !rootStyles.getPropertyValue(gridTemplateRowsVarName).trim()
    // ) {
    //     gridTemplateRowsVarName = "--layout-grid-rows-portrait";
    // }

    return {
        gridTemplateRows: `var(${gridTemplateRowsVarName})`,
    };
};

export const headerContainerEditorShow = {
    border: "0.15rem dashed var(--header-show)",
};

export const trialTimerContainer = {
    width: "var(--timer-container-width)",
    height: "var(--timer-container-height)",
    backgroundColor: "var(--timer-container-bg-color)",
    top: "var(--timer-position-top)",
    right: "var(--timer-position-right)",
    left: "var(--timer-position-left)",
    bottom: "var(--timer-position-bottom)",
};

export const trialTimerSVGContainer = {
    width: "var(--timer-container-width)",
    height: "var(--timer-container-height)",
};

export const trialTimerCircle = {
    strokeWidth: "var(--timer-circle-stroke-width)",
    stroke: "var(--timer-circle-stroke-color)",
};

export const trialTimerCircleTrack = {
    strokeWidth: "var(--timer-circle-stroke-width)",
    stroke: "var(--timer-circle-track-stroke-color)",
};

export const trialTimerText = {
    color: "var(--timer-text-color)",
    fontSize: "var(--timer-text-font-size)",
};
