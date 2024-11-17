/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./dist/**/*.{html,js,php}",
    "*/*.{html,js,php}"],
  theme: {
    extend: {
      colors: { 
        Tgray : "rgb(180 180 180)",
        bgcolor : "rgb(1, 3, 10)",
        darkblue : "rgb(14, 14, 14)",
        debt1 : "#f0d6b2",
        debt2 : "#ebd9c2",
        debt5 : "rgb(25, 85, 124)",
        playstation : "rgb(0, 112, 209)",
        green1 : "rgb(8 189 75)",

      },
      fontFamily: {
        bodyfont: ["Poppins"]
      },
      boxShadow: {
        'shalg': '10px 5px 40px rgba(0, 0, 0, 0.1)',
      },
      backgroundColor: {
        linear: "linear-gradient(0deg, rgba(1,3,10,1) 0%, rgba(255,174,0,0) 25%)"
      },

    },
  },
  plugins: [],

}

