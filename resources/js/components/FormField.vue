<template>
    <default-field :field="field" :errors="errors">
        <template slot="field">
            <div class="ltr" id="coord-panel">
                <textarea
                    @change="inputChanged"
                    :id="field.attribute"
                    v-model="value"
                    name="coords"
                    class="canvas-area w-full block border border-gray-200 rounded mb-2 p-2"
                    :class="errorClasses"
                    :disabled="disabled"
                    placeholder="Shape Coordinates"
                    :data-image-url="image_url">
                </textarea>
                <canvas
                    @mousedown="mousedown"
                    @contextmenu="rightclick"
                    @mouseup="stopdrag"
                    id="coord-canvas">
                </canvas>
                <button @click="reset" type="button" class="btn btn-default btn-danger inline-flex items-center relative" id="coord-reset">Clear</button>
            </div>
        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field', 'errorClasses'],
    
    data() {
        return {
            parentValue: null,
            loaded: false,
            image_url: '',
            points: [],
            input: null,
            canvas: null,
            reset: null,
            image: null,
            activePoint: null,
            ctx: null,
            startpoint: false
        }
    },

    computed: {
        pointsJoined() {
            return this.points.join(',')
        },

        watchedComponents() {
            return this.$parent.$children.filter(component => {
                return this.isWatchingComponent(component);
            });
        },

        disabled() {
            return this.image_url == '';
        }
    },

    methods: {

        resize() {
            this.canvas.setAttribute('height', this.image.height);
            this.canvas.setAttribute('width', this.image.width);
            this.draw();
        },

        init() {
            var v = this.field.value ? this.field.value.replace(/[^0-9\,]/ig, '') : '';

            if (v.length) {
                this.points = v.split(',').map(function (point) {
                    return parseInt(point, 10);
                });
            } else {
                this.points = [];
            }

            this.reset = document.getElementById('coord-reset');
            this.canvas = document.getElementById('coord-canvas');
            this.ctx = this.canvas.getContext('2d');

            this.image = new Image();

            this.image.addEventListener('load', this.resize);
            this.image.src = this.image_url;
            if (this.image.loaded) {
                this.resize();
            }

            this.canvas.style.background = 'url(' + this.image.src + ')';
        },

        move(e) {
            if (!e.offsetX) {
                e.offsetX = (e.pageX - e.target.offsetLeft);
                e.offsetY = (e.pageY - e.target.offsetTop);
            }
            this.points[this.activePoint] = Math.round(e.offsetX);
            this.points[this.activePoint + 1] = Math.round(e.offsetY);
            this.draw();
        },

        moveall(e) {
            if (!e.offsetX) {
                e.offsetX = (e.pageX - e.target.offsetLeft);
                e.offsetY = (e.pageY - e.target.offsetTop);
            }
            if (!this.startpoint) {
                this.startpoint = {x: Math.round(e.offsetX), y: Math.round(e.offsetY)};
            }
            var sdvpoint = {x: Math.round(e.offsetX), y: Math.round(e.offsetY)};
            for (var i = 0; i < this.points.length; i++) {
                this.points[i] = (sdvpoint.x - this.startpoint.x) + this.points[i];
                this.points[++i] = (sdvpoint.y - this.startpoint.y) + this.points[i];
            }
            this.startpoint = sdvpoint;
            this.draw();
        },

        getCenter() {
            var ptc = [];
            for (i = 0; i < this.points.length; i++) {
                ptc.push({x: this.points[i], y: this.points[++i]});
            }
            var first = ptc[0], last = ptc[ptc.length - 1];
            if (first.x != last.x || first.y != last.y) ptc.push(first);
            var twicearea = 0,
                x = 0, y = 0,
                nptc = ptc.length,
                p1, p2, f;
            for (var i = 0, j = nptc - 1; i < nptc; j = i++) {
                p1 = ptc[i];
                p2 = ptc[j];
                f = p1.x * p2.y - p2.x * p1.y;
                twicearea += f;
                x += ( p1.x + p2.x ) * f;
                y += ( p1.y + p2.y ) * f;
            }
            f = twicearea * 3;
            return {x: x / f, y: y / f};
        },

        reset() {
            this.points = [];
            this.draw();
        },

        inputChanged() {
            var v = this.points.replace(/[^0-9\,]/ig, '');
            if (v.length) {
                points = v.split(',').map(function (point) {
                    return parseInt(point, 10);
                });
            } else {
                points = [];
            }
            this.draw();
        },

        record() {
            this.value = this.points.join(',')
        },

        draw() {
            this.ctx.canvas.width = this.ctx.canvas.width;

            this.record();
            if (this.points.length < 2) {
                return;
            }
            this.ctx.globalCompositeOperation = 'destination-over';
            this.ctx.fillStyle = 'rgb(255,255,255)';
            this.ctx.strokeStyle = 'rgb(255,20,20)';
            this.ctx.lineWidth = 1;
            if (this.points.length >= 6) {
                var c = this.getCenter();
                this.ctx.fillRect(c.x - 4, c.y - 4, 8, 8);
            }
            this.ctx.beginPath();
            this.ctx.moveTo(this.points[0], this.points[1]);
            for (var i = 0; i < this.points.length; i += 2) {
                this.ctx.fillRect(this.points[i] - 2, this.points[i + 1] - 2, 4, 4);
                this.ctx.strokeRect(this.points[i] - 2, this.points[i + 1] - 2, 4, 4);
                if (this.points.length > 2 && i > 1) {
                    this.ctx.lineTo(this.points[i], this.points[i + 1]);
                }
            }
            this.ctx.closePath();
            this.ctx.fillStyle = 'rgba(255,0,0,0.3)';
            this.ctx.fill();
            this.ctx.stroke();
        },

        stopdrag() {
            this.canvas.removeEventListener('mousemove', this.move);
            this.canvas.removeEventListener('mousemove', this.moveall);
            this.canvas.mousemove = null;
            this.record();
            this.activePoint = null;
        },

        rightclick(e) {
            e.preventDefault();
            if (!e.offsetX) {
                e.offsetX = (e.pageX - e.target.offsetLeft);
                e.offsetY = (e.pageY - e.target.offsetTop);
            }
            var x = e.offsetX, y = e.offsetY;
            for (var i = 0; i < this.points.length; i += 2) {
                if ( (Math.sqrt(Math.pow(x - this.points[i], 2) + Math.pow(y - this.points[i + 1], 2)))  < 6) {
                    this.points.splice(i, 2);
                    this.draw();
                    this.record();
                    return false;
                }
            }
            return false;
        },

        mousedown(e) {
            var x, y, dis, lineDis, insertAt = this.points.length;

            if (e.which === 3) {
                return false;
            }

            e.preventDefault();
            if (!e.offsetX) {
                e.offsetX = (e.pageX - e.target.offsetLeft);
                e.offsetY = (e.pageY - e.target.offsetTop);
            }
            x = e.offsetX;
            y = e.offsetY;

            if (this.points.length >= 6) {
                var c = this.getCenter();
                this.ctx.fillRect(c.x - 4, c.y - 4, 8, 8);
                dis = Math.sqrt(Math.pow(x - c.x, 2) + Math.pow(y - c.y, 2));
                if (dis < 6) {
                    this.startpoint = false;
                    this.canvas.addEventListener('mousemove', this.moveall);
                    this.image.addEventListener('load', this.resize)
                    return false;
                }
            }

            for (var i = 0; i < this.points.length; i += 2) {
                dis = Math.sqrt(Math.pow(x - this.points[i], 2) + Math.pow(y - this.points[i + 1], 2));
                if (dis < 6) {
                    this.activePoint = i;
                    this.canvas.addEventListener('mousemove', this.move);
                    return false;
                }
            }

            for (var i = 0; i < this.points.length; i += 2) {
                if (i > 1) {
                    lineDis = this.dotLineLength(
                        x, y,
                        this.points[i], this.points[i + 1],
                        this.points[i - 2], this.points[i - 1],
                        true
                    );
                    if (lineDis < 6) {
                        insertAt = i;
                    }
                }
            }

            this.points.splice(insertAt, 0, Math.round(x), Math.round(y));
            this.activePoint = insertAt;
            this.canvas.addEventListener('mousemove', this.move);

            this.draw();
            this.record();

            return false;
        },

        lineLength(x, y, x0, y0) {
            return Math.sqrt((x -= x0) * x + (y -= y0) * y);
        },

        dotLineLength(x, y, x0, y0, x1, y1, o) {
            if (o && !(o = function (x, y, x0, y0, x1, y1) {
                if (!(x1 - x0)) return {x: x0, y: y};
                else if (!(y1 - y0)) return {x: x, y: y0};
                var left, tg = -1 / ((y1 - y0) / (x1 - x0));
                return {
                    x: left = (x1 * (x * tg - y + y0) + x0 * (x * -tg + y - y1)) / (tg * (x1 - x0) + y0 - y1),
                    y: tg * left - tg * x + y
                };
            }(x, y, x0, y0, x1, y1), o.x >= Math.min(x0, x1) && o.x <= Math.max(x0, x1) && o.y >= Math.min(y0, y1) && o.y <= Math.max(y0, y1))) {
                var l1 = this.lineLength(x, y, x0, y0), l2 = this.lineLength(x, y, x1, y1);
                return l1 > l2 ? l2 : l1;
            }
            else {
                var a = y0 - y1, b = x1 - x0, c = x0 * y1 - y0 * x1;
                return Math.abs(a * x + b * y + c) / Math.sqrt(a * a + b * b);
            }
        },

        extend (target, source) {
            target = target || {};
            for (var prop in source) {
                if (typeof source[prop] === 'object') {
                    target[prop] = this.extend(target[prop], source[prop]);
                } else {
                    target[prop] = source[prop];
                }
            }
            return target;
        },

        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
            this.value = ""
        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            formData.append(this.field.attribute, this.value || '')
        },

        /**
         * Update the field's internal value.
         */
        handleChange(value) {
            this.value = value
        },

        updateImage() {
            this.value = "";
            this.loaded = false;
            this.image_url = "";
            let self = this;

            if (this.parentValue != null && this.parentValue != "") {
                Nova.request()
                    .get(`/nova-vendor/location-map-selector/${this.resourceName}`, {
                        params: {
                            attribute: this.field.attribute,
                            parent: this.parentValue
                        }
                    })
                    .then(response => {
                        self.loaded = true;
                        self.image_url = response.data;
                        self.init();
                    });
            }
        },

        isWatchingComponent(component) {
            return (
                component.field !== undefined &&
                component.field.attribute == this.field.parentAttribute
            );
        }
    },

    mounted() {
        this.watchedComponents.forEach(component => {
            let attribute = "value";

            if (component.field.component === "belongs-to-field") {
                attribute = "selectedResource";
            }

            component.$watch(
                attribute,
                value => {
                    this.parentValue =
                        value && attribute == "selectedResource"
                            ? value.value
                            : value;

                    this.updateImage();
                },
                { immediate: true }
            );
        });
    }
}
</script>

<style scoped>
</style>
