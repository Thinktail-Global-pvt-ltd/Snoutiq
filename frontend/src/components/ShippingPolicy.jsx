import React from "react";

export default function ShippingPolicy() {
  return (
    <div className="max-w-5xl mx-auto px-4 py-8 text-gray-800">
      <h1 className="text-2xl font-bold mb-2">Shipping & Delivery Policy</h1>
      <p className="text-sm text-gray-500 mb-6">Last Updated: May 31, 2025</p>

      <p className="mb-6">
        This Shipping & Delivery Policy (“Policy”) explains how orders for
        physical products (pet food, toys, medicines, supplies, and accessories)
        are handled on the Snoutiq website and mobile application (together,
        “Snoutiq” or the “Platform”), operated by Thinktail Global Pvt. Ltd.
        (“Thinktail,” “we,” “us,” or “our”). By placing a product order on
        Snoutiq, you (“Buyer” or “you”) agree to the terms of this Policy.
        Sellers (“Seller” or “Provider”) must comply fully with these guidelines
        when fulfilling and shipping orders.
      </p>

      {/* Sections */}
      <Section title="1. Definitions & Scope">
        <Definition term="Buyer">
          Any Guest User or Registered User who purchases physical products on
          Snoutiq.
        </Definition>
        <Definition term="Seller">
          Any registered individual or entity (retailer, pharmacy, or pet
          supplies vendor) that lists and sells physical products on Snoutiq.
          Sellers must complete KYC and maintain necessary business and
          regulatory registrations.
        </Definition>
        <Definition term="Order">
          A confirmed purchase request placed by a Buyer for one or more
          physical products.
        </Definition>
        <Definition term="Shipping Partner">
          A third-party logistics provider (e.g., Blinkit, Porter, Dunzo,
          Delhivery) contracted by Seller to pick up and deliver products to
          Buyers.
        </Definition>
        <Definition term="Dispatch Time">
          The interval between when an Order is placed and when the product is
          handed over by Seller to the Shipping Partner.
        </Definition>
        <Definition term="Transit Time">
          The interval between pickup by the Shipping Partner and attempted
          delivery to the Buyer’s address.
        </Definition>
        <Definition term="Delivery Window">
          Estimated timeframe provided to the Buyer during checkout (e.g.,
          “Within 5-7 business days,” or “Express: 24–48 hours”).
        </Definition>
        <Definition term="Tracking">
          Real-time shipment status updated by the Shipping Partner, accessible
          in the Buyer’s order details.
        </Definition>
        <Definition term="Delivery Attempt">
          A single visit by the Shipping Partner to the delivery address. If
          unsuccessful, follow-up attempts or return processes apply.
        </Definition>
      </Section>

      <Section title="2. Seller Responsibilities">
        <SubSection subtitle="Order Acceptance & Confirmation">
          Once a Buyer places an Order, Seller must review and accept it within
          2 hours. If Seller cannot fulfill the Order, they must cancel it in
          Snoutiq within the same 2-hour window.
        </SubSection>
        <SubSection subtitle="Preparing Products for Dispatch">
          <ul className="list-disc ml-6 mb-4">
            <li>
              Use sturdy, clean boxes or packaging materials suitable for pet
              items.
            </li>
            <li>
              For temperature-sensitive products, use insulated packaging with
              ice packs or gel packs.
            </li>
            <li>
              For fragile items, use bubble wrap or similar cushioning.
            </li>
            <li>
              For hazardous/regulated items, comply with labeling and
              documentation laws.
            </li>
          </ul>
          <p>
            Labels must include Buyer’s address, Seller’s return address, and a
            packing slip inside.
          </p>
        </SubSection>
        <SubSection subtitle="Hand-Over to Shipping Partner">
          Seller must hand over Orders within 5 hours of acceptance. Delays must
          be communicated within Snoutiq.
        </SubSection>
        <SubSection subtitle="Updating Shipment Status">
          After handover, Seller must mark Order as “Shipped” and add tracking.
        </SubSection>
        <SubSection subtitle="Shipping Charges & Free Shipping Thresholds">
          Shipping is calculated by weight, size, and speed. Free shipping
          applies when eligible (e.g., Orders above ₹1,000).
        </SubSection>
        <SubSection subtitle="Communication & Issue Resolution">
          Seller must resolve courier issues (damage, loss) within 48 hours.
        </SubSection>
      </Section>

      {/* Similarly, continue rendering other major sections (3-10) with <Section> */}

      <Section title="10. Modifications to this Policy">
        Thinktail may update this Shipping & Delivery Policy from time to time.
        Material changes will be communicated 7 days in advance. Continued use
        of Snoutiq implies acceptance.
      </Section>
    </div>
  );
}

function Section({ title, children }) {
  return (
    <div className="mb-8">
      <h2 className="text-xl font-semibold mb-3">{title}</h2>
      <div className="space-y-3 text-gray-700 text-sm leading-relaxed">
        {children}
      </div>
    </div>
  );
}

function SubSection({ subtitle, children }) {
  return (
    <div className="mb-4">
      <h3 className="font-medium mb-1">{subtitle}</h3>
      <div>{children}</div>
    </div>
  );
}

function Definition({ term, children }) {
  return (
    <div className="mb-2">
      <span className="font-medium">{term}: </span>
      <span>{children}</span>
    </div>
  );
}
