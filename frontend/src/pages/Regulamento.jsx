import { useEffect } from "react"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import NavBar from "../components/NavBar/NavBar"

function Regulamento(){
    useEffect(()=>{
        document.title = "SGM - Regulamento"
    })

    return(
        <>
        <BannerGlobal titulo="Regulamentos"/>
        <NavBar />
        </>
    )
}

export default Regulamento