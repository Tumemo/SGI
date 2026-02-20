import { useEffect } from "react"
import BannerGlobal from "../Componentes/BannerGlobal/BannerGlobal"
import NavBar from "../Componentes/NavBar/NavBar"

function Ranking(){
    useEffect(()=>{
        document.title = "SGM - Ranking"
    },[])

    return(
        <>
            <BannerGlobal />
            
            <NavBar />
        </>
    )
}

export default Ranking