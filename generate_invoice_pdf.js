// This is a Node.js script that can be deployed as a serverless function
// or run as a standalone API endpoint.

import puppeteer from "puppeteer"

export default async function handler(req, res) {
  // Ensure this is a GET request (or POST if you prefer)
  if (req.method !== "GET") {
    return res.status(405).json({ message: "Method Not Allowed" })
  }

  const { invoice_id, php_base_url } = req.query

  if (!invoice_id || !php_base_url) {
    return res.status(400).json({ message: "Missing invoice_id or php_base_url parameter." })
  }

  // Construct the full URL to your invoice1.php
  // IMPORTANT: This URL MUST be publicly accessible from where this Node.js script runs.
  const invoiceUrl = `${php_base_url}/invoice1.php?id=${invoice_id}`

  let browser
  try {
    browser = await puppeteer.launch({
      headless: true, // Use 'new' for new headless mode, or 'true' for old
      args: ["--no-sandbox", "--disable-setuid-sandbox"], // Required for some environments like Vercel
    })
    const page = await browser.newPage()

    // Set a reasonable timeout for navigation
    await page.goto(invoiceUrl, { waitUntil: "networkidle0", timeout: 60000 }) // Wait for network to be idle

    // Optional: Wait for specific elements to ensure content is loaded
    // await page.waitForSelector('.invoice-container');

    // Generate PDF
    const pdfBuffer = await page.pdf({
      format: "A4",
      printBackground: true, // Ensure background colors/images are printed
      margin: {
        top: "0mm",
        right: "0mm",
        bottom: "0mm",
        left: "0mm",
      },
      // You can also use 'pageRanges' if your invoice spans multiple pages and you want specific ones
      // pageRanges: '1',
    })

    res.setHeader("Content-Type", "application/pdf")
    res.setHeader("Content-Disposition", `attachment; filename=invoice_${invoice_id}.pdf`)
    res.send(pdfBuffer)
  } catch (error) {
    console.error("Error generating PDF:", error)
    res.status(500).json({ message: "Failed to generate PDF", error: error.message })
  } finally {
    if (browser) {
      await browser.close()
    }
  }
}
