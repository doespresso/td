# THIS PROJECT IS DEPRICATED now that Animate.css includeds this functionality: http://daneden.github.io/animate.css/

# Animate.css Helpers

## Overview

**Animate.css Helpers** helps you control [Animate.css's](https://github.com/daneden/animate.css) animation.  Specifically, you can easily control animation timing, delays and automatic staggering of elements using [Sass](http://sass-lang.com/). 

With one line, you can animate one, or stagger all elements in a UI area.

### Looper Example

	#dropDown.active {
		>div a{
      		@include animationLooper(3,0.1s,0.2s,fadeInDown);
    	}
	}
	
This will stagger and animate all links that have a parent container with a class of "active".  The first will animate using "fadeInDown" over 0.2 seconds.  After 0.1 seconds have elapsed, the next one will animate and so on.  This will do the first 3 items in that set.

### Normal Example

	#element.active {
      @include animateHelper(1s);
	}
	
This will fade in the element with class "active" after 1 second.

## Installation
Clone the repo to your project and simply import it with Sass like so:

	@import "path/to/animate.css_helpers";
	
This helper includes animate.css so you don't need to also clone that repo unless you want to.  In that case, just delete those classes.

You can also use Bower:

	bower install animate.css-helpers --save


## Animation Helper

	@mixin animateHelper($delay:0,$duration:0.2s,$name:fadeIn,$easing:ease-out,$iterationCount:1,$direction:normal,$fillmode:both)

### Delay
The number of seconds before animation occurs.

### Duration
The length of the animation in seconds.

### Name
The name of the [Animate.css](https://github.com/daneden/animate.css) effect.

### Easing
The CSS easing function name.  You can use [Ceaser](http://matthewlein.com/ceaser/) to generate custom easing curves.

### IterationCount
The number of times to play the animation.

### Direction
The direction to play the animation.  Normal or backwards.

### Fill Mode
The CSS animation fill mode.  If you are unsure, leave the default.

## Animation Looper

	animationLooper($iterations:1,$offset:0,$duration:0.2s,$name:fadeIn,$inverted:false,$easing:ease-out,$iterationCount:1,$direction:normal,$fillmode:both){
	
### Iterations
Number of elements to apply animation to.

### Offset
The number of seconds to wait until animating the next element.

### Duration
The length of each animation in seconds.

### Name
The name of the [Animate.css](https://github.com/daneden/animate.css) effect.

### Inverted
Will animate the elements in the opposite order.  Useful if you have floated elements where the first node is visually the last.

### Easing
The CSS easing function name.  You can use [Ceaser](http://matthewlein.com/ceaser/) to generate custom easing curves.

### IterationCount
The number of times to play each animation.

### Direction
The direction to play the animation.  Normal or backwards.

### Fill Mode
The CSS animation fill mode.  If you are unsure, leave the default.

