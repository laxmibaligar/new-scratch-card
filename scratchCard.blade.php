@extends('layouts.strides')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
    const hasGift = @json(!!($gift && $gift->media)); 
</script>

<script>
function copyVoucherCode() {
    const voucherCode = document.getElementById("voucherCode").value;

    const tempInput = document.createElement("textarea");
    tempInput.value = voucherCode;
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); 

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            const copyMsg = document.getElementById("copyMessage");
            copyMsg.style.display = "inline";
            setTimeout(() => {
                copyMsg.style.display = "none";
            }, 2000); 
        }
    } catch (err) {
        console.error("Copy failed", err);
    }

    document.body.removeChild(tempInput);
}
</script>



<script>

window.addEventListener('DOMContentLoaded', function () {
    const loader = document.getElementById('scratchLoader');
    const popupType = window.popupType;

    // If result page
    if (popupType === 'final') {
        loader.style.display = 'none';
        document.getElementById('finalPopup').style.display = 'block';
        return;
    }


    // If scratch page
    let canvas = document.getElementById('scratchCanvas');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    if (!canvas) return;

    let ctx = canvas.getContext('2d', { willReadFrequently: true });
    let isDrawing = false;
    let revealed = false;

    let coverImage = new Image();
    coverImage.src = '/images/Mega Giveaway.png';

    coverImage.onload = function () {
        ctx.drawImage(coverImage, 0, 0, canvas.width, canvas.height);
        ctx.globalCompositeOperation = 'destination-out';
        loader.style.display = 'none';
        canvas.style.opacity = '1';
    };

    const movementThreshold = 5;
    let lastTouch = { x: null, y: null };
    let lastMouse = { x: null, y: null };

   function getXY(e) {
    const rect = canvas.getBoundingClientRect();
    const clientX = e.clientX || e.touches?.[0]?.clientX;
    const clientY = e.clientY || e.touches?.[0]?.clientY;

    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    return {
        x: (clientX - rect.left) * scaleX,
        y: (clientY - rect.top) * scaleY
    };
}


    function scratch(e) {
        if (!isDrawing) return;

        const { x, y } = getXY(e);
        const fromX = lastMouse.x || lastTouch.x;
        const fromY = lastMouse.y || lastTouch.y;

        ctx.lineJoin = ctx.lineCap = 'round';
        ctx.lineWidth = 40;
        ctx.beginPath();
        ctx.moveTo(fromX, fromY);
        ctx.lineTo(x, y);
        ctx.stroke();

        if (!revealed && getScratchedPercentage() > 20) {
            revealed = true;
            canvas.style.transition = 'opacity 1s ease';
            canvas.style.opacity = '0';

            const sprinkle = document.getElementById('sprinkleOverlay');
            if (sprinkle) sprinkle.style.display = 'block';

          setTimeout(() => {
            const giftId = {{ $gift->id ?? 'null' }};

            if (giftId !== null) {
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: { y: 0.6 }
                });

                const confettiSound = document.getElementById('confettiSound');
                if (confettiSound) confettiSound.play().catch(console.error);
            }
        }, 500);

            setTimeout(() => {
                if (sprinkle) sprinkle.style.display = 'none';
                window.location.reload();
            }, 4000);

            fetch("/save-scratch-card", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    employee_id: {{ $employee->id }},
                    company_id: {{ $employee->company_id }},
                    gift_id: {{ $gift->id ?? 'null' }}
                })
            }).then(res => res.json()).then(data => {
                console.log(data.message);
            }).catch(console.error);
        }
    }

    function getScratchedPercentage() {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        let totalPixels = imageData.data.length / 4;
        let transparentPixels = 0;

        for (let i = 0; i < imageData.data.length; i += 4) {
            if (imageData.data[i + 3] === 0) transparentPixels++;
        }
        return (transparentPixels / totalPixels) * 100;
    }

    canvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        const pos = getXY(e);
        lastMouse.x = pos.x;
        lastMouse.y = pos.y;
    });
    canvas.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        const pos = getXY(e);
        const dx = pos.x - lastMouse.x;
        const dy = pos.y - lastMouse.y;
        if (Math.sqrt(dx * dx + dy * dy) > movementThreshold) {
            scratch(e);
            lastMouse = pos;
        }
    });
    canvas.addEventListener('mouseup', () => isDrawing = false);
    canvas.addEventListener('mouseleave', () => isDrawing = false);

    canvas.addEventListener('touchstart', (e) => {
        isDrawing = true;
        const pos = getXY(e);
        lastTouch.x = pos.x;
        lastTouch.y = pos.y;
    });
    canvas.addEventListener('touchmove', (e) => {
        if (!isDrawing) return;
        e.preventDefault();
        const pos = getXY(e);
        const dx = pos.x - lastTouch.x;
        const dy = pos.y - lastTouch.y;
        if (Math.sqrt(dx * dx + dy * dy) > movementThreshold) {
            scratch(e);
            lastTouch = pos;
        }
    });
    canvas.addEventListener('touchend', () => isDrawing = false);
    canvas.addEventListener('touchcancel', () => isDrawing = false);
});
</script>

@endpush
@push('styles')
<style>
   
html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    font-family: sans-serif;
}

#app {
    width: 100vw;
    min-height: 100vh; 
    /* background-image: url("{{ asset('images/background-image.jpg') }}"); */
    background-color: white;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
}
.scratch-page {
    width: 100%;
    max-width: 600px;
    padding: 15px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh; 
    transform: translateY(-8vh); 
}


.title {
    color:black;
    text-align: center;
    font-size: 3.2rem;
    margin-bottom: 50px;
}
.title h4{
    color:#87054F;
    font-weight: bold;
}

.scratch-container {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

#scratch-area {
    width: 100%;
    max-width: 450px;
    aspect-ratio: 5 / 3;
    background-color:#F4B120;
    border-radius: 10px !important;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

#scratch-area img#giftImage {
    z-index: 1;
    position: absolute;
    top: 5%;
    left: 5%;
    width: 90%;
    height: 90%;
    object-fit: contain;
    border-radius: 10px;
    
   
}

canvas#scratchCanvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100% !important;
    height: 100% !important;
    touch-action: none;
    z-index: 3;
}

#finalPopup {
    background-color: #F4B120;
    padding: 20px;
    border-radius: 10px;
    width: 95%;
    max-width: 500px;
    margin: 20px auto;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    box-sizing: border-box;
}


#finalPopup .popup-content h2,
#finalPopup .popup-content h3 {
    text-align: center;
    margin: 10px 0;
    font-size: 1.5rem;
    padding: 5px;
    word-wrap: break-word;
}
.popup-content img {
    width: 100%;
    max-width: 250px;
    height: auto;
    border-radius: 10px;
    display: block;
    margin: 10px auto;
}
#sprinkleOverlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: none;
    z-index: 9999;
}

#sprinkleOverlay img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#confettiCanvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 10000;
}

.sprinkle {
    z-index: 9998;
    pointer-events: none;
}

footer {
    display: none !important;
}

.nextTime {
    font-size: 1.4rem;
    font-weight: bold;
    text-align: center;
}
label{
    color: black !important;
}


</style>


@endpush


@section('content')
<div id="scratchLoader" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: white;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
">
    <span style="font-size: 18px;">load..</span>
</div>

<div class="scratch-page">
    <div id="sprinkleOverlay" class="sprinkle">
        <canvas id="confettiCanvas"></canvas>
    </div>
    <audio id="confettiSound" src="{{ asset('audio/confetti.mp3') }}"></audio>

    <div class="scratch-container">
        @if($scratchCard->is_done == 1 && $gift)
            <script>window.popupType = 'final';</script>

            <div id="finalPopup" class="fullscreen-popup" style="display: none;">
                <div class="title" style="margin-top: 40px; text-align: center;">
                    <img src="{{ asset('images/Congrats.png') }}" alt="Mega Giveaway" style="width: 100%; max-width: 350px; height: auto; margin-bottom: 20px;" />
                </div>

                <div class="popup-content" style="text-align: center;">
                    <img src="{{ asset($gift->media) }}" alt="{{ $gift->name }}" class="gift"
                        style="max-width: 300px; margin: -50px auto 20px auto; display: block;" />
                    <!-- <h3 style="margin-bottom: 20px;">You've won a {{ $gift->name }}</h3> -->
                     <h3 style="margin-bottom: 20px;">
                            @if(Str::contains(strtolower($gift->name), 'silver'))
                                You've won a {{ $gift->name }}
                            @elseif(Str::contains(strtolower($gift->name), 'amazon'))
                                You've won an {{ $gift->name }}
                            @else
                                You've won {{ $gift->name }}
                            @endif
                        </h3>

                    @if(in_array($scratchCard->gift_id, [3, 4, 5]))
                        <div style="display: flex; flex-direction: column; align-items: center; margin-top: 15px; color: black;">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: center;">
                                <label for="voucherCode" style="font-size: 18px;">Voucher Code:</label>
                                <input type="text" id="voucherCode" value="{{ $scratchCard->voucher_code }}" readonly
                                    style="width: 200px; font-size: 16px; border: none; outline: none; background: transparent; margin-top: 10px; margin-bottom: 10px;">
                                <button onclick="copyVoucherCode()" style="font-size: 16px; padding: 8px 12px; cursor: pointer;">Copy</button>
                            </div>
                            <span id="copyMessage" style="color: black; font-size: 14px; margin-top: 5px; display: none;">Copied!</span>
                        </div>
                    @endif
                </div>
            </div>

        @elseif($scratchCard->is_done == 1 && $gift == null)
            <script>window.popupType = 'final';</script>

              <div id="finalPopup" class="fullscreen-popup" style="display: none;">
                <div class="popup-content" style="text-align: center;">
                    <h3 style="margin-bottom: 20px;">Whoops! Better Luck Next Time</h3>
                    <img src="{{ asset('images/oops.jpg') }}" alt="Better luck next time" />
                </div>
            </div>

        @else
            <script>window.popupType = 'scratch';</script>

            <div class="title" style="margin-top: 5vh; text-align: center;">
                <h4 style="margin-top: 1vh;">Scratch the card to reveal your rewards</h4>
            </div>

            <div id="scratch-area" style="position: relative; background-color: #F4B120; border-radius: 10px !important; overflow: hidden;">
                @if($gift && $gift->media)
                    <div class="image-wrapper">
                    <img id="giftImage" src="{{ asset($gift->media) }}" alt="Gift" />
                </div>
                @else
                    <p class="nextTime" style="text-align: center;">Whoops! Better Luck Next Time</p>
                @endif

                <canvas id="scratchCanvas"  style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 2; opacity: 0;"></canvas>
            </div>
        @endif
    </div>
</div>
@endsection


