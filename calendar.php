<?php
$pageTitle = 'Book a Session';
$metaDesc  = 'Interactive calendar booking for online therapy sessions with Holistic Wellness.';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/payments.php';
require_once __DIR__ . '/includes/header.php';

// Get existing bookings to mark as unavailable
$pdo = getDB();
$bookings = $pdo->query("SELECT preferred_date, preferred_time FROM bookings WHERE status IN ('pending', 'confirmed')")->fetchAll(PDO::FETCH_ASSOC);

// Convert to calendar events
$bookedSlots = [];
foreach ($bookings as $booking) {
    $date = $booking['preferred_date'];
    $time = $booking['preferred_time'];

    // Map time slots to hours
    $timeSlots = [
        'Morning (9am-12pm)' => ['09:00', '10:00', '11:00'],
        'Afternoon (1pm-5pm)' => ['13:00', '14:00', '15:00', '16:00'],
        'Evening (6pm-9pm)' => ['18:00', '19:00', '20:00']
    ];

    if (isset($timeSlots[$time])) {
        foreach ($timeSlots[$time] as $hour) {
            $bookedSlots[] = $date . 'T' . $hour . ':00';
        }
    }
}
?>

<style>
.calendar-container {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    padding: 2rem;
    margin-bottom: 2rem;
}

.calendar-header {
    text-align: center;
    margin-bottom: 2rem;
}

.calendar-header h2 {
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.calendar-header p {
    color: #666;
}

#calendar {
    max-width: 100%;
    margin: 0 auto;
}

.fc {
    font-family: inherit;
}

.fc-button {
    background: var(--primary) !important;
    border: none !important;
    border-radius: 6px !important;
}

.fc-button:hover {
    background: var(--primary-dark) !important;
}

.fc-day-today {
    background: rgba(90, 125, 124, 0.1) !important;
}

.fc-day-disabled {
    background: #f5f5f5 !important;
    color: #ccc !important;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.payment-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
}

.payment-option:hover {
    border-color: var(--primary);
}

.payment-option input[type="radio"] {
    margin: 0;
}

.payment-option input[type="radio"]:checked + .payment-label {
    color: var(--primary);
}

.payment-label {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.payment-label i {
    font-size: 1.5rem;
    color: var(--accent);
}

.payment-label small {
    color: #666;
    font-size: 0.8rem;
}

.payment-fields {
    margin-top: 1rem;
}

.booking-form {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section h3 {
    color: var(--primary);
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .calendar-container {
        padding: 1rem;
    }

    .time-slots {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<div class="page-header">
    <div class="container">
        <h1>Interactive Calendar Booking</h1>
        <p>Select your preferred date and time from our available slots.</p>
    </div>
</div>

<section>
    <div class="container">
        <?php renderFlash(); ?>

        <div class="calendar-container">
            <div class="calendar-header">
                <h2>Select a Date</h2>
                <p>Click on an available date to view time slots</p>
            </div>
            <div id="calendar"></div>
        </div>

        <div id="timeSelection" style="display: none;">
            <div class="calendar-container">
                <h3 id="selectedDateTitle">Available Times for [Date]</h3>
                <div class="time-slots" id="timeSlots">
                    <!-- Time slots will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <div id="bookingForm" style="display: none;">
            <div class="booking-form">
                <h3>Complete Your Booking</h3>
                <form method="POST" action="actions/book.php" id="calendarBookingForm">
                    <input type="hidden" name="selected_date" id="selectedDateInput">
                    <input type="hidden" name="selected_time" id="selectedTimeInput">

                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name <span style="color:red">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address <span style="color:red">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Service Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="service">Service Type <span style="color:red">*</span></label>
                                <select id="service" name="service" class="form-control" required>
                                    <option value="">Select a service...</option>
                                    <option value="Individual Therapy" data-price="3500">Individual Therapy - KES 3,500</option>
                                    <option value="Couples Therapy" data-price="5000">Couples Therapy - KES 5,000</option>
                                    <option value="Anxiety & Depression" data-price="3500">Anxiety & Depression Support - KES 3,500</option>
                                    <option value="Life Coaching" data-price="4000">Life Coaching - KES 4,000</option>
                                    <option value="Initial Consultation" data-price="0">Initial Consultation (Free)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Selected Date & Time</label>
                                <div style="padding: 0.75rem; background: #f8f9fa; border-radius: 6px; border: 1px solid #e0e0e0;">
                                    <span id="selectedDateTime">Please select date and time above</span>
                                </div>
                            </div>
                        </div>
                        <div id="pricingInfo" style="display: none; margin-top: 1rem; padding: 1rem; background: #e8f5e8; border-radius: 8px; border-left: 4px solid var(--accent);">
                            <h4 style="margin: 0 0 0.5rem 0; color: var(--primary);">Session Cost: <span id="servicePrice">KES 0</span></h4>
                            <p style="margin: 0; font-size: 0.9rem; color: #555;">Payment due within 24 hours of booking confirmation.</p>
                        </div>
                    </div>

                    <div class="form-section" id="paymentSection" style="display: none;">
                        <h3>Payment Method</h3>
                        <div class="payment-methods">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="mpesa" checked>
                                <span class="payment-label">
                                    <i class="fas fa-mobile-alt"></i>
                                    M-Pesa
                                    <small>Pay with your mobile money</small>
                                </span>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="card">
                                <span class="payment-label">
                                    <i class="fas fa-credit-card"></i>
                                    Credit/Debit Card
                                    <small>Visa, Mastercard, etc.</small>
                                </span>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="paypal">
                                <span class="payment-label">
                                    <i class="fab fa-paypal"></i>
                                    PayPal
                                    <small>Pay with PayPal account</small>
                                </span>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bank">
                                <span class="payment-label">
                                    <i class="fas fa-university"></i>
                                    Bank Transfer
                                    <small>Direct bank transfer</small>
                                </span>
                            </label>
                        </div>
                        <div id="mpesaFields" class="payment-fields">
                            <div class="form-group">
                                <label for="phone">M-Pesa Phone Number <span style="color:red">*</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control" placeholder="254712345678" pattern="254[0-9]{9}">
                                <small style="color: #666;">Format: 254XXXXXXXXX (without + or spaces)</small>
                            </div>
                        </div>
                        <div id="cardFields" class="payment-fields" style="display: none;">
                            <div class="form-group">
                                <label>Card Information</label>
                                <div id="card-element" style="padding: 0.75rem; border: 1px solid #e0e0e0; border-radius: 6px; background: white; margin-bottom: 1rem;">
                                    <!-- Stripe Card Element will be inserted here -->
                                </div>
                                <div id="card-errors" style="color: #dc2626; font-size: 0.9rem; display: none;"></div>
                            </div>
                            <div id="stripe-payment-button" style="display: none;">
                                <button type="button" id="stripePayBtn" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-credit-card"></i> Pay KES <span id="stripeAmount">0</span>
                                </button>
                            </div>
                        </div>
                        <div id="paypalFields" class="payment-fields" style="display: none;">
                            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                <p style="margin-bottom: 1rem;">You will be redirected to PayPal to complete your payment securely.</p>
                                <button type="button" id="paypalPayBtn" class="btn btn-primary">
                                    <i class="fab fa-paypal"></i> Continue to PayPal
                                </button>
                            </div>
                        </div>
                        <div id="bankFields" class="payment-fields" style="display: none;">
                            <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--primary);">
                                <h4 style="margin: 0 0 1rem 0; color: var(--primary);">Bank Transfer Details</h4>
                                <div style="background: white; padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 0.9rem;">
                                    <div><strong>Bank:</strong> KCB Bank Kenya</div>
                                    <div><strong>Account Name:</strong> Holistic Wellness Ltd</div>
                                    <div><strong>Account Number:</strong> 1234567890</div>
                                    <div><strong>Branch:</strong> Westlands Branch</div>
                                    <div><strong>Swift Code:</strong> KCBLKENX</div>
                                </div>
                                <p style="margin: 1rem 0 0 0; font-size: 0.9rem; color: #666;">
                                    Please include your booking reference in the payment description.
                                    Your booking will be confirmed once payment is received (usually within 1-2 business days).
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Additional Information</h3>
                        <div class="form-group">
                            <label for="message">Tell us about your needs <span style="color:#aaa;font-weight:400">(optional)</span></label>
                            <textarea id="message" name="message" class="form-control" rows="4" placeholder="Share any specific concerns, goals, or questions you have..."></textarea>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-calendar-check"></i> Confirm Booking
                        </button>
                        <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                            You'll receive a confirmation email and WhatsApp message within 24 hours.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<!-- Stripe JS SDK -->
<script src="https://js.stripe.com/v3/"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookedSlots = <?= json_encode($bookedSlots) ?>;
    let selectedDate = null;
    let selectedTime = null;
    let stripe = null;
    let cardElement = null;
    let currentBookingId = null;

    // Initialize Stripe
    if (typeof Stripe !== 'undefined') {
        stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
        const elements = stripe.elements();

        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                }
            }
        });
    }

    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5, 6], // Monday - Saturday
            startTime: '09:00',
            endTime: '21:00'
        },
        validRange: {
            start: new Date().toISOString().split('T')[0] // Today onwards
        },
        dateClick: function(info) {
            // Check if date is in the past
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const clickedDate = new Date(info.dateStr);

            if (clickedDate < today) {
                alert('Please select a future date.');
                return;
            }

            // Check if it's Sunday (closed)
            if (clickedDate.getDay() === 0) {
                alert('We are closed on Sundays. Please select another date.');
                return;
            }

            selectedDate = info.dateStr;
            showTimeSlots(selectedDate);
        },
        eventDidMount: function(info) {
            // Style booked slots
            if (info.event.extendedProps.booked) {
                info.el.style.backgroundColor = '#f5f5f5';
                info.el.style.color = '#999';
                info.el.style.pointerEvents = 'none';
            }
        }
    });

    calendar.render();

    function showTimeSlots(date) {
        const timeSelection = document.getElementById('timeSelection');
        const selectedDateTitle = document.getElementById('selectedDateTitle');
        const timeSlotsContainer = document.getElementById('timeSlots');

        selectedDateTitle.textContent = `Available Times for ${new Date(date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })}`;

        // Define available time slots based on day
        const dateObj = new Date(date);
        const dayOfWeek = dateObj.getDay();

        let timeOptions = [];
        if (dayOfWeek === 6) { // Saturday
            timeOptions = [
                { label: '10:00 AM', value: '10:00' },
                { label: '11:00 AM', value: '11:00' },
                { label: '2:00 PM', value: '14:00' },
                { label: '3:00 PM', value: '15:00' }
            ];
        } else { // Monday - Friday
            timeOptions = [
                { label: '9:00 AM', value: '09:00' },
                { label: '10:00 AM', value: '10:00' },
                { label: '11:00 AM', value: '11:00' },
                { label: '1:00 PM', value: '13:00' },
                { label: '2:00 PM', value: '14:00' },
                { label: '3:00 PM', value: '15:00' },
                { label: '4:00 PM', value: '16:00' },
                { label: '6:00 PM', value: '18:00' },
                { label: '7:00 PM', value: '19:00' },
                { label: '8:00 PM', value: '20:00' }
            ];
        }

        timeSlotsContainer.innerHTML = '';

        timeOptions.forEach(slot => {
            const slotDateTime = `${date}T${slot.value}:00`;
            const isBooked = bookedSlots.includes(slotDateTime);

            const slotEl = document.createElement('div');
            slotEl.className = `time-slot ${isBooked ? 'booked' : ''}`;
            slotEl.textContent = slot.label;
            slotEl.dataset.time = slot.value;
            slotEl.dataset.label = slot.label;

            if (!isBooked) {
                slotEl.addEventListener('click', () => selectTimeSlot(slotEl, slot.value, slot.label));
            }

            timeSlotsContainer.appendChild(slotEl);
        });

        timeSelection.style.display = 'block';
        document.getElementById('bookingForm').style.display = 'none';
        selectedTime = null;
    }

    function selectTimeSlot(element, timeValue, timeLabel) {
        // Remove previous selection
        document.querySelectorAll('.time-slot.selected').forEach(el => {
            el.classList.remove('selected');
        });

        // Select new slot
        element.classList.add('selected');
        selectedTime = timeValue;

        // Show booking form
        document.getElementById('bookingForm').style.display = 'block';

        // Update form inputs
        document.getElementById('selectedDateInput').value = selectedDate;
        document.getElementById('selectedTimeInput').value = timeLabel;
        document.getElementById('selectedDateTime').textContent = `${new Date(selectedDate).toLocaleDateString()} at ${timeLabel}`;

        // Scroll to form
        document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
    }

    // Stripe payment handler
    document.getElementById('stripePayBtn').addEventListener('click', async function() {
        const serviceSelect = document.getElementById('service');
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const amount = parseInt(selectedOption.getAttribute('data-price') || 0);

        if (!stripe || !cardElement) {
            alert('Stripe is not properly initialized. Please refresh the page.');
            return;
        }

        if (amount === 0) {
            alert('Please select a paid service.');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        try {
            // First submit the booking form to create the booking
            const formData = new FormData(document.getElementById('calendarBookingForm'));
            const bookingResponse = await fetch('actions/book.php', {
                method: 'POST',
                body: formData
            });

            const bookingResult = await bookingResponse.json();

            if (!bookingResult.success) {
                throw new Error(bookingResult.message || 'Failed to create booking');
            }

            currentBookingId = bookingResult.booking_id;

            // Create payment intent
            const paymentResponse = await fetch('payments/stripe_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_payment_intent',
                    booking_id: currentBookingId,
                    amount: amount
                })
            });

            const paymentResult = await paymentResponse.json();

            if (!paymentResult.success) {
                throw new Error('Failed to initialize payment');
            }

            // Confirm payment with card
            const { error, paymentIntent } = await stripe.confirmCardPayment(
                paymentResult.client_secret,
                {
                    payment_method: {
                        card: cardElement
                    }
                }
            );

            if (error) {
                throw new Error(error.message);
            }

            if (paymentIntent.status === 'succeeded') {
                // Confirm payment on server
                await fetch('payments/stripe_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'confirm_payment',
                        payment_intent_id: paymentIntent.id,
                        booking_id: currentBookingId
                    })
                });

                alert('Payment successful! You will receive a confirmation email shortly.');
                window.location.href = 'dashboard.php?tab=bookings';
            }

        } catch (error) {
            alert('Payment failed: ' + error.message);
            document.getElementById('card-errors').textContent = error.message;
            document.getElementById('card-errors').style.display = 'block';
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-credit-card"></i> Pay KES <span id="stripeAmount">0</span>';
        }
    });

    // PayPal payment handler
    document.getElementById('paypalPayBtn').addEventListener('click', async function() {
        const serviceSelect = document.getElementById('service');
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const amount = parseInt(selectedOption.getAttribute('data-price') || 0);

        if (amount === 0) {
            alert('Please select a paid service.');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting...';

        try {
            // Create booking first
            const formData = new FormData(document.getElementById('calendarBookingForm'));
            const bookingResponse = await fetch('actions/book.php', {
                method: 'POST',
                body: formData
            });

            const bookingResult = await bookingResponse.json();

            if (!bookingResult.success) {
                throw new Error(bookingResult.message || 'Failed to create booking');
            }

            // Create PayPal payment
            const paymentResponse = await fetch('payments/paypal_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'create_payment',
                    booking_id: bookingResult.booking_id,
                    amount: amount,
                    description: selectedOption.text
                })
            });

            const paymentResult = await paymentResponse.json();

            if (paymentResult.success) {
                // Redirect to PayPal
                window.location.href = paymentResult.approval_url;
            } else {
                throw new Error('Failed to create PayPal payment');
            }

        } catch (error) {
            alert('Payment setup failed: ' + error.message);
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fab fa-paypal"></i> Continue to PayPal';
        }
    });

    // Service selection handler
    document.getElementById('service').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const price = selectedOption.getAttribute('data-price') || 0;
        const pricingInfo = document.getElementById('pricingInfo');
        const servicePrice = document.getElementById('servicePrice');
        const paymentSection = document.getElementById('paymentSection');

        if (price > 0) {
            servicePrice.textContent = `KES ${parseInt(price).toLocaleString()}`;
            pricingInfo.style.display = 'block';
            paymentSection.style.display = 'block';
        } else {
            pricingInfo.style.display = 'none';
            paymentSection.style.display = 'none';
        }

        // Update Stripe amount if card is selected
        const cardRadio = document.querySelector('input[name="payment_method"][value="card"]');
        if (cardRadio && cardRadio.checked) {
            document.getElementById('stripeAmount').textContent = parseInt(price).toLocaleString();
        }
    });

    // Payment method handler
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const mpesaFields = document.getElementById('mpesaFields');
            const cardFields = document.getElementById('cardFields');
            const paypalFields = document.getElementById('paypalFields');
            const bankFields = document.getElementById('bankFields');
            const stripeButton = document.getElementById('stripe-payment-button');

            // Hide all fields first
            mpesaFields.style.display = 'none';
            cardFields.style.display = 'none';
            paypalFields.style.display = 'none';
            bankFields.style.display = 'none';
            stripeButton.style.display = 'none';

            if (this.value === 'mpesa') {
                mpesaFields.style.display = 'block';
            } else if (this.value === 'card') {
                cardFields.style.display = 'block';
                stripeButton.style.display = 'block';

                // Mount Stripe card element
                if (cardElement && !cardElement._mounted) {
                    cardElement.mount('#card-element');
                    cardElement._mounted = true;
                }

                // Update amount display
                const serviceSelect = document.getElementById('service');
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price') || 0;
                document.getElementById('stripeAmount').textContent = price.toLocaleString();
            } else if (this.value === 'paypal') {
                paypalFields.style.display = 'block';
            } else if (this.value === 'bank') {
                bankFields.style.display = 'block';
            }
        });
    });

    // Form submission handler
    document.getElementById('calendarBookingForm').addEventListener('submit', async function(e) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const serviceSelect = document.getElementById('service');
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const amount = parseInt(selectedOption.getAttribute('data-price') || 0);

        // For card and PayPal, prevent default submission and handle via JS
        if ((paymentMethod === 'card' || paymentMethod === 'paypal') && amount > 0) {
            e.preventDefault();

            if (paymentMethod === 'card') {
                document.getElementById('stripePayBtn').click();
            } else if (paymentMethod === 'paypal') {
                document.getElementById('paypalPayBtn').click();
            }
            return false;
        }

        // For M-Pesa and bank transfer, allow normal form submission
        // The server-side handler will process these
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
